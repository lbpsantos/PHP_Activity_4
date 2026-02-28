<?php
require_once 'auth.php';
require_login();
require_once __DIR__ . '/Model/ProgramModel.php';

$search = trim($_GET['q'] ?? '');
$sort = $_GET['sort'] ?? 'title';
$programModel = new Program();

// Pull user filters then ask the model for the matching dataset.
$listResult = $programModel->read([
    'search' => $search,
    'sort' => $sort
]);
$programs = $listResult['programs'] ?? [];
$loadError = $listResult['success'] ? '' : ($listResult['error'] ?: 'Unable to load programs.');
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
                        $currentSort = $sort;
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
                if ($loadError !== '') {
                    // Surface backend failure so staff know something went wrong.
                    echo "<tr><td colspan='4' style='text-align:center;color:#c00;'>" . htmlspecialchars($loadError, ENT_QUOTES) . "</td></tr>";
                } elseif (!empty($programs)) {
                    foreach ($programs as $row) {
                        $id = htmlspecialchars((string) $row['program_id'], ENT_QUOTES);
                        $code = htmlspecialchars((string) $row['code'], ENT_QUOTES);
                        $title = htmlspecialchars((string) $row['title'], ENT_QUOTES);
                        $years = htmlspecialchars((string) $row['years'], ENT_QUOTES);

                        echo "<tr>" .
                             "<td>{$code}</td>" .
                             "<td>{$title}</td>" .
                             "<td>{$years}</td>" .
                             "<td><a class=\"link\" href=\"program_edit.php?program_id={$id}\">Edit</a></td>" .
                             "</tr>";
                    }
                } else {
                    echo "<tr><td colspan='4' style='text-align:center;'>No records found</td></tr>";
                }
            ?>
        </table>


    </div>
</body>
</html>