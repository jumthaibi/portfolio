<?php
// Temporary diagnostic file — delete it as soon as you're done checking
require 'db.php';

echo "<h2>Checking the admin table</h2>";

try {
    $stmt = $pdo->query("SELECT * FROM admin");
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (count($rows) === 0) {
        echo "<p style='color:red;'><strong>There are no rows in the admin table.</strong> This is the problem — the table is empty, so no admin user was ever created.</p>";
    } else {
        foreach ($rows as $r) {
            echo "<p><strong>username:</strong> " . htmlspecialchars($r['username']) . "</p>";
            echo "<p><strong>password (as stored):</strong> " . htmlspecialchars($r['password']) . "</p>";

            $looksHashed = (strpos($r['password'], '$2y$') === 0);
            echo "<p><strong>Is it a valid hash?</strong> " . ($looksHashed ? "✅ Yes, it looks like a proper hash" : "❌ No, this is not a hash — it's stored as plain, unencrypted text") . "</p>";
        }
    }
} catch (\PDOException $e) {
    echo "<p style='color:red;'>Connection or query error: " . htmlspecialchars($e->getMessage()) . "</p>";
}

echo "<hr><h2>Manual password test</h2>";
echo "<form method='GET'>
    Username: <input type='text' name='u' value='" . htmlspecialchars($_GET['u'] ?? '') . "'><br><br>
    Password you're testing: <input type='text' name='p' value='" . htmlspecialchars($_GET['p'] ?? '') . "'><br><br>
    <button type='submit'>Test</button>
</form>";

if (isset($_GET['u']) && isset($_GET['p']) && $_GET['u'] !== '') {
    $stmt = $pdo->prepare("SELECT * FROM admin WHERE username = ?");
    $stmt->execute([$_GET['u']]);
    $admin = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$admin) {
        echo "<p style='color:red;'>No user with that username exists in the table. Check the spelling (no extra spaces).</p>";
    } else {
        $ok = password_verify($_GET['p'], $admin['password']);
        echo "<p>password_verify result: " . ($ok ? "✅ Match — login should work normally" : "❌ No match") . "</p>";
    }
}
?>