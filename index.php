<?php
// Connexion
$host = 'localhost';
$user = 'todolist';
$pass = '3c5k76bpHW3LWJKz';
$name = 'todolist';
$conn = new mysqli($host, $user, $pass, $name);
if ($conn->connect_error) { die('Connection failed: ' . htmlspecialchars($conn->connect_error)); }
$conn->set_charset('utf8mb4');

// Device ID
$device_id = $_COOKIE['device_id'] ?? '';
if (!$device_id) {
    $device_id = bin2hex(random_bytes(16));
    setcookie('device_id', $device_id, time() + 31536000, '/', '', false, true);
}

// Reset complet
if (isset($_GET['reset']) && $_GET['reset'] === '1') {
    $stmt = $conn->prepare("DELETE FROM tasks WHERE device_id = ?");
    $stmt->bind_param("s", $device_id);
    $stmt->execute();
    $stmt->close();
    setcookie('device_id', '', time() - 3600, '/');
    header("Location: index.php");
    exit;
}

// Ajouter
if (isset($_POST["addtask"])) {
    $task = trim($_POST["task"] ?? '');
    if ($task !== '') {
        $stmt = $conn->prepare("INSERT INTO tasks (task, device_id) VALUES (?, ?)");
        $stmt->bind_param("ss", $task, $device_id);
        $stmt->execute();
        $stmt->close();
    }
    header("Location: index.php");
    exit;
}

// Supprimer
if (isset($_GET["delete"])) {
    $id = (int) $_GET["delete"];
    $stmt = $conn->prepare("DELETE FROM tasks WHERE id = ? AND device_id = ?");
    $stmt->bind_param("is", $id, $device_id);
    $stmt->execute();
    $stmt->close();
    header("Location: index.php");
    exit;
}

// Compléter
if (isset($_GET["complete"])) {
    $id = (int) $_GET["complete"];
    $stmt = $conn->prepare("UPDATE tasks SET status = 'completed' WHERE id = ? AND device_id = ?");
    $stmt->bind_param("is", $id, $device_id);
    $stmt->execute();
    $stmt->close();
    header("Location: index.php");
    exit;
}

// Récupération
$stmt = $conn->prepare("SELECT id, task, status FROM tasks WHERE device_id = ? ORDER BY id DESC");
$stmt->bind_param("s", $device_id);
$stmt->execute();
$result = $stmt->get_result();
$stmt->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8" />
<meta name="viewport" content="width=device-width, initial-scale=1.0"/>
<title>To-Do List - PIN Lock</title>
<script src="https://cdn.tailwindcss.com"></script>
<style>
  .pin-input { letter-spacing: 10px; font-size: 2rem; text-align: center; background: transparent; border: none; border-bottom: 2px solid #3b82f6; outline: none; color: #3b82f6; width: 150px; }
  .pin-pad { display: grid; grid-template-columns: repeat(3, 80px); gap: 10px; margin-top: 20px; }
  .pin-btn { background: white; border: 1px solid #ccc; font-size: 1.5rem; padding: 15px; border-radius: 10px; cursor: pointer; transition: 0.2s; }
  .pin-btn:hover { background: #f3f4f6; }
</style>
</head>
<body class="bg-gray-100 min-h-screen flex items-center justify-center">

<!-- Écran PIN -->
<div id="pin-screen" class="bg-white shadow-lg rounded-xl p-8 w-full max-w-sm text-center" style="display:none">
  <h1 class="text-2xl font-bold mb-4" id="pin-title">🔑 Créez votre PIN</h1>
  <input id="pin-input" class="pin-input" type="password" maxlength="6" placeholder=""/>
  <div class="pin-pad mt-4" id="pin-pad"></div>
  <button id="reset-btn" class="mt-4 text-sm text-red-500 hover:underline">Réinitialiser (efface tout)</button>
</div>

<!-- Todo List -->
<div id="todo-app" class="bg-white shadow-lg rounded-xl p-8 w-full max-w-md" style="display:none">
  <h1 class="text-2xl font-bold text-center text-gray-800 mb-6">📝 Todo List</h1>
  <form action="index.php" method="post" class="flex gap-2">
    <input type="text" name="task" placeholder="Enter new task"
      class="flex-1 px-4 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500" required/>
    <button type="submit" name="addtask"
      class="bg-blue-600 text-white px-4 py-2 rounded-md hover:bg-blue-700 transition">Add</button>
  </form>
  <ul class="mt-6 space-y-2">
    <?php while ($row = $result->fetch_assoc()): ?>
      <?php $isCompleted = $row["status"] === "completed"; ?>
      <li class="bg-gray-50 px-4 py-2 rounded border border-gray-200 flex justify-between items-center">
        <span class="font-medium <?= $isCompleted ? "line-through text-gray-400" : "text-gray-800" ?>">
          <?= htmlspecialchars($row["task"], ENT_QUOTES, 'UTF-8') ?>
        </span>
        <div class="flex gap-2 text-sm">
          <?php if (!$isCompleted): ?>
            <a href="index.php?complete=<?= (int)$row['id'] ?>" class="text-green-600 hover:underline">Complete</a>
          <?php endif; ?>
          <a href="index.php?delete=<?= (int)$row['id'] ?>" class="text-red-600 hover:underline">Delete</a>
        </div>
      </li>
    <?php endwhile; ?>
  </ul>
</div>

<script>
const padContainer = document.getElementById('pin-pad');
const numbers = [1,2,3,4,5,6,7,8,9,'←',0,'OK'];
numbers.forEach(num => {
  const btn = document.createElement('button');
  btn.className = 'pin-btn';
  btn.textContent = num;
  btn.type = 'button';
  btn.onclick = () => handlePad(num);
  padContainer.appendChild(btn);
});

const pinInput = document.getElementById('pin-input');
const resetBtn = document.getElementById('reset-btn');
const todoApp = document.getElementById('todo-app');
const pinScreen = document.getElementById('pin-screen');
const pinTitle = document.getElementById('pin-title');

function handlePad(num) {
  if (num === '←') {
    pinInput.value = pinInput.value.slice(0, -1);
  } else if (num === 'OK') {
    verifyPin();
  } else {
    if (pinInput.value.length < 6) pinInput.value += num;
  }
}

function verifyPin() {
  const storedPin = localStorage.getItem('todo_pin');
  if (!storedPin) {
    localStorage.setItem('todo_pin', pinInput.value);
    sessionStorage.setItem('unlocked', '1');
    showTodo();
  } else if (storedPin === pinInput.value) {
    sessionStorage.setItem('unlocked', '1');
    showTodo();
  } else {
    alert("PIN incorrect");
  }
  pinInput.value = '';
}

function showTodo() {
  pinScreen.style.display = 'none';
  todoApp.style.display = 'block';
}

resetBtn.onclick = () => {
  if (confirm("Réinitialiser : toutes vos tâches seront effacées. Continuer ?")) {
    localStorage.removeItem('todo_pin');
    sessionStorage.removeItem('unlocked');
    window.location.href = "?reset=1";
  }
};

// Affichage au chargement
if (sessionStorage.getItem('unlocked') === '1') {
  showTodo();
} else {
  pinScreen.style.display = 'block';
  if (!localStorage.getItem('todo_pin')) {
    pinTitle.textContent = "🔑 Créez votre PIN";
  } else {
    pinTitle.textContent = "🔒 Entrez votre PIN";
  }
}
</script>
</body>
</html>
