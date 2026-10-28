<?php
session_start();
require_once __DIR__ . '/Class/Database.php';

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

class LoginDatabase extends Database
{
    public function authenticate($username, $password)
    {
        $connection = $this->getConnection();
        $query = $connection->prepare(
            'SELECT id_number, username, password FROM `user` WHERE username = ? LIMIT 1'
        );
        $query->bind_param('s', $username);
        $query->execute();
        $query->bind_result($userId, $savedUsername, $savedPassword);

        if ($query->fetch() && $password === $savedPassword) {
            $user = [
                'id_number' => $userId,
                'username' => $savedUsername,
            ];
            $query->close();
            return $user;
        } else {
            $query->close();
            return false;
        }
    }
}

$error = '';
$username = '';

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    if (
        !isset($_POST['csrf_token'])
        || !is_string($_POST['csrf_token'])
        || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])
    ) {
        $error = 'Please refresh the page and try again.';
    } elseif (isset($_POST['logout'])) {
        session_unset();
        session_destroy();
        header('Location: login.php');
        exit;
    } else {
        $username = isset($_POST['username']) && is_string($_POST['username'])
            ? trim($_POST['username'])
            : '';
        $password = isset($_POST['password']) && is_string($_POST['password'])
            ? $_POST['password']
            : '';

        if ($username === '' || $password === '') {
            $error = 'Enter your username and password.';
        } else {
            try {
                $loginDatabase = new LoginDatabase();
                $user = $loginDatabase->authenticate($username, $password);

                if ($user) {
                    session_regenerate_id(true);
                    $_SESSION['user_id'] = $user['id_number'];
                    $_SESSION['username'] = $user['username'];
                    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
                    header('Location: login.php');
                    exit;
                } else {
                    $error = 'The username or password is incorrect.';
                }
            } catch (RuntimeException | mysqli_sql_exception $exception) {
                error_log($exception->getMessage());
                $error = 'Unable to sign in right now. Please try again later.';
            }
        }
    }
}
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Login</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
    <main class="container min-vh-100 d-flex align-items-center justify-content-center py-4">
        <div class="row w-100 justify-content-center">
            <div class="col-12 col-sm-8 col-md-6 col-lg-4">
                <div class="card shadow-sm">
                    <div class="card-body p-4">
                        <?php if (isset($_SESSION['username'])): ?>
                            <h1 class="h3 text-center mb-3">Welcome</h1>
                            <p class="text-center">
                                You are logged in as <?= htmlspecialchars($_SESSION['username'], ENT_QUOTES, 'UTF-8') ?>.
                            </p>
                            <form method="post">
                                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8') ?>">
                                <button class="btn btn-primary w-100" type="submit" name="logout">Log out</button>
                            </form>
                        <?php else: ?>
                            <h1 class="h3 text-center mb-2">Welcome</h1>
                            <p class="text-center text-secondary mb-4">Sign in to your account</p>

                            <?php if ($error !== ''): ?>
                                <div class="alert alert-danger" role="alert">
                                    <?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?>
                                </div>
                            <?php endif; ?>

                            <form method="post">
                                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8') ?>">

                                <div class="mb-3">
                                    <label class="form-label" for="username">Username</label>
                                    <input
                                        class="form-control"
                                        type="text"
                                        id="username"
                                        name="username"
                                        placeholder="Enter your username"
                                        value="<?= htmlspecialchars($username, ENT_QUOTES, 'UTF-8') ?>"
                                        maxlength="50"
                                        autocomplete="username"
                                        required
                                    >
                                </div>

                                <div class="mb-3">
                                    <label class="form-label" for="password">Password</label>
                                    <input
                                        class="form-control"
                                        type="password"
                                        id="password"
                                        name="password"
                                        placeholder="Enter your password"
                                        autocomplete="current-password"
                                        required
                                    >
                                </div>

                                <button class="btn btn-primary w-100" type="submit">Log in</button>
                            </form>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </main>
</body>
</html>
