<?php
/**
 * This is a sample view that demonstrates the use of the NoCon\Users package.
 * 
 * A database with tables must be created and a database configuration file 
 * provided with the connection details before the sample will function.
 */

namespace NoCon;

$userDatabaseConfig = Framework\Config::get('database');


if ( isset($_POST['logout']) ) {
    $_SESSION['NoConUser'] = null;
    unset($_SESSION['NoConUser']);
}


if ( isset($_POST['login']) ) {
    $user = new Users\User($userDatabaseConfig);
    if ( $user->authenticate($_POST['username'], $_POST['password']) ) {
        $_SESSION['NoConUser'] = $user->getUser();
    }
    else {
        $error = 'Login failed';
    }
}


if ( isset($_SESSION['NoConUser']) ) {
    $user = new Users\User($userDatabaseConfig, $_SESSION['NoConUser']);
}


if ( isset($_POST['create']) ) {
    $user->create($_POST['username'], $_POST['password']);
    $user->updateFlags(Users\User::ACTIVE_FLAG, $_POST['username']);
}


if ( isset($_POST['delete']) ) {
    $user->delete($_POST['username']);
}


if ( isset($_POST['activate']) ) {
    $userRow = $user->getUser($_POST['username']);
    if ( $userRow ) {
        $user->updateFlags($userRow['flags'] | Users\User::ACTIVE_FLAG, $_POST['username']);
    }
}


if ( isset($_POST['deactivate']) ) {
    $userRow = $user->getUser($_POST['username']);
    if ( $userRow ) {
        $user->updateFlags($userRow['flags'] ^ ($userRow['flags'] & Users\User::ACTIVE_FLAG), $_POST['username']);
    }
}


?>


<h1>NoCon Users Package Demo</h1>

<ul>
    <li>Create database tables.</li>
    <li>Insert administrative user into table.</li>
    <li>Add database configuration file.</li>
</ul>


<?php
if ( !empty($error) ) {
?>

<b>ERROR: </b><?php echo $error; ?><br>

<?php
}
?>


<hr>


<?php
if ( empty($_SESSION['NoConUser']) ) {
?>


<form method="post">
    Username:<br>
    <input type="text" name="username"><br>
    Password:<br>
    <input type="text" name="password"><br>
    <button type="submit" name="login">Login</button> 
</form>


<?php
}
else {
    $userDetails = $user->getUser();
?>


<h2>Current User</h2>
<b>Username: </b><?php echo $userDetails['username']; ?><br>
<b>Created: </b><?php echo $userDetails['created']; ?><br>
<b>Last Login: </b><?php echo $userDetails['lastLogin']; ?><br>
<b>Is Admin: </b><?php echo $user->isAdmin() ? 'yes' : 'no'; ?><br>

<form method="post">
    <button type="submit" name="logout">Logout</button> 
</form>


<?php
    if ( $user->isAdmin() ) {
?>


<br>
<h2>Create User</h2>
<form method="post">
    Username:<br>
    <input type="text" name="username"><br>
    Password:<br>
    <input type="text" name="password"><br>
    <button type="submit" name="create">Create</button>
</form>

<br>
<h2>User Changes</h2>
<form method="post">
    Username:<br>
    <input type="text" name="username"><br>
    <button type="submit" name="delete">Delete</button>
    <button type="submit" name="activate">Activate</button>
    <button type="submit" name="deactivate">Deactivate</button>
</form>

<hr>

<?php $userList = $user->getUsers(); ?>
<table border="1">
    
    <?php foreach ( $userList as $userRow ) { ?>
    <tr><th>Username</th><th>Created</th><th>Last Login</th><th>Flags</th></tr>
    <tr>
        <td><?php echo $userRow['username']; ?></td>
        <td><?php echo $userRow['created']; ?></td>
        <td><?php echo $userRow['lastLogin']; ?></td>
        <td><?php echo $userRow['flags']; ?></td>
    </tr>
    <?php } ?>
</table>


<?php
    } // if isAdmin
}