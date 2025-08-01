<?php
$conn = new mysqli("localhost","root","","todolist");
if(!$conn){
    die("connection failed". $conn->connect_error);
}
if(isset($_POST["addtask"])){
  $task= $_POST["task"];
  $conn->query("INSERT INTO tasks(task) VALUES('$task')");
  header("location: index.php");
}

if(isset($_GET["delete"])){
    $id= $_GET["delete"];
    $conn->query(" DELETE FROM tasks WHERE id = '$id' ");
    header("location: index.php");
}

if(isset($_GET["complete"])){
    $id= $_GET["complete"];
    $conn->query(" UPDATE tasks SET status = 'completed' WHERE id = '$id' ");
    header("location: index.php");
}

$result= $conn->query("SELECT * FROM tasks ORDER BY id DESC");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>To-Do List</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 min-h-screen flex items-center justify-center">

    <div class="bg-white shadow-lg rounded-xl p-8 w-full max-w-md">
        <h1 class="text-2xl font-bold text-center text-gray-800 mb-6">📝 Todo List</h1>

        <form action="index.php" method="post" class="flex gap-2">
            <input 
                type="text" 
                name="task" 
                placeholder="Enter new task"
                class="flex-1 px-4 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                required
            >
            <button 
                type="submit" 
                name="addtask"
                class="bg-blue-600 text-white px-4 py-2 rounded-md hover:bg-blue-700 transition"
            >
                Add
            </button>
        </form>

        <!-- Here you can display the list of tasks -->
    <ul class="mt-6 space-y-2">
    <?php while($row = $result->fetch_assoc()): ?>
        <?php 
            $isCompleted = $row["status"] === "completed";
            $taskClass = $isCompleted ? "line-through text-gray-400" : "text-gray-800";
        ?>
      
        <li class="bg-gray-50 px-4 py-2 rounded border border-gray-200 flex justify-between items-center">
            <span class="font-medium <?php echo $taskClass; ?>">
                <?php echo htmlspecialchars($row["task"]); ?>
            </span>
            <div class="flex gap-2 text-sm">
                <?php if (!$isCompleted): ?>
                    <a href="index.php?complete=<?php echo $row['id']; ?>" class="text-green-600 hover:underline">Complete</a>
                <?php endif; ?>
                <a href="index.php?delete=<?php echo $row['id']; ?>" class="text-red-600 hover:underline">Delete</a>
            </div>
        </li>
    <?php endwhile; ?>
</ul>


    </div>

</body>
</html>
