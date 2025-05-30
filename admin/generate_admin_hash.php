<?php
// --- IMPORTANT SECURITY NOTE ---
// DELETE THIS FILE FROM YOUR SERVER IMMEDIATELY AFTER USE.
// Exposing this file can be a security risk.

// --- Configuration ---
// Replace 'YourSupposedlyCorrectPassword' with the actual password you are trying to use for the admin.
$plainPassword = 'password123'; // Change this to the desired password

// --- Password Hashing ---
// PASSWORD_DEFAULT is recommended as it will use the strongest algorithm available
// and may be updated as new, stronger algorithms are added to PHP.
$hashedPassword = password_hash($plainPassword, PASSWORD_DEFAULT);

// --- Output ---
if ($hashedPassword === false) {
    echo "Password hashing failed. Check your PHP version and configuration.";
} else {
    echo "Plain Password: " . htmlspecialchars($plainPassword) . "<br>";
    echo "Hashed Password (for database): " . htmlspecialchars($hashedPassword) . "<br>";
    echo "<p><strong>Copy the 'Hashed Password' value above and use it to update the admin's password in your database.</strong></p>";
    echo "<p style='color:red; font-weight:bold;'>REMEMBER TO DELETE THIS FILE ('generate_admin_hash.php') FROM YOUR SERVER NOW!</p>";
}

// You can also check if a known hash matches a password:
// $knownHashFromDatabase = '$2y$10$someExistingHash...'; // Replace with an actual hash from your DB
// if (password_verify($plainPassword, $knownHashFromDatabase)) {
//     echo 'Verification with known hash: Password matches.<br>';
// } else {
//     echo 'Verification with known hash: Password does NOT match.<br>';
// }
?>