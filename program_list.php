<?php
require_once 'auth.php';
require_login();
require_once 'db.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Subject List</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 40px; color: #222; }
        .container { max-width: 960px; margin: 0 auto; }
        h2 { display: flex; justify-content: space-between; align-items: center; }
        a.btn { display: inline-block; padding: 8px 12px; border-radius: 6px; text-decoration: none; color: #fff; background: #0d6efd;  }
        a.btnhome { display: inline-block; padding: 8px 12px; border-radius: 6px; text-decoration: none; color: #fff; background: #5c5d61;  }
        a.link { color: #0d6efd; text-decoration: none; }
        table { width: 100%; border-collapse: collapse; margin-top: 12px; }
        th, td { border: 1px solid #ddd; padding: 10px; text-align: left; }
        th { background: #f6f6f6; }
        .nav { margin-bottom: 12px; }
        .toolbar{display: flex; align-items: center; justify-content: space-between; gap: 16px; flex-wrap: wrap; }
        .search-form { display: flex; gap: 8px; }
        .search-form input, .search-form select { padding: 6px 10px; border: 1px solid #bbb; border-radius: 4px; }
        .search-form button { border: none; padding: 6px 12px; border-radius: 4px; background: #198754; color: #fff; cursor: pointer; }
    </style>
</head>
<body>
    <div class ="container">
        <!--Back to home button-->
        <div class ="nav">
            <a class = "btnhome" href="home.php">Back to Homepage</a>
        </div>

        <div class="toolbar">
            <h2>PROGRAMS</h2>
            <form class="search-form" method="get" action="program_list.php">
                <input type="text" name="q" placeholder="Search code, title, or years" value="<?php echo isset($_GET['q']) ? htmlspecialchars($_GET['q'], ENT_QUOTES) : ''; ?>">
                <select name="sort">
                    <?php
                        $currentSort = $_GET['sort'] ?? 'title';
                        $options = [
                            'title' => 'Title (A-Z)',
                            'code' => 'Code (A-Z)',
                            'years' => 'Years (Low-High)'
                        ];
                        foreach ($options as $value => $label) {
                            $selected = $currentSort === $value ? 'selected' : '';
                            echo "<option value=\"{$value}\" {$selected}>{$label}</option>";
                        }
                    ?>
                </select>
                <button type="submit">Search</button>
            </form>
            <a class="btn" href="program_new.php">Add New Program</a>
        </div>

        <table>
            <tr>
                <th>Code</th>
                <th>Title</th>
                <th>Years</th>
                <th>Action</th>
            </tr>

            <?php
                // Pull search term if provided
                $search = trim($_GET['q'] ?? '');
                $sort = $_GET['sort'] ?? 'title';
                $sql = "SELECT program_id, code, title, years FROM program";

                if ($search !== '') {
                    // Filter by code, title, or years when searching
                    $like = '%' . $conn->real_escape_string($search) . '%';
                    $sql .= " WHERE code LIKE '{$like}' OR title LIKE '{$like}' OR CAST(years AS CHAR) LIKE '{$like}'";
                }

                $validSort = ['title' => 'title', 'code' => 'code', 'years' => 'years'];
                $orderColumn = $validSort[$sort] ?? 'title';
                $sql .= " ORDER BY {$orderColumn}";
                $result = $conn -> query($sql);

                if($result -> num_rows > 0){
                    while($row = $result -> fetch_assoc()){
                        $id = htmlspecialchars($row['program_id']);
                        $code = htmlspecialchars($row['code']);
                        $title = htmlspecialchars($row['title']);
                            $years = htmlspecialchars($row['years']);
                        echo "<tr>" .
                             "<td>{$code}</td>" .
                             "<td>{$title}</td>" .
                                "<td>{$years}</td>" .
                             "<td><a class=\"link\" href=\"program_edit.php?program_id={$id}\">Edit</a></td>" .
                             "</tr>";
                    }       
                }else{
                    echo "<tr><td colspan='4' style='text-align:center;'>No records found</td></tr>";
                }
            ?>
        </table>


    </div>
</body>
</html>