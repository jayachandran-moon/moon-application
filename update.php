<?php
include 'connect.php';

$msg = ""; // Initialize message variable
$is_update = false;
$current_id = '';

// Initialize form variables
$name = $secondname = $phone = $textcontent = '';

// Check if we're in update mode
if(isset($_GET['updateid'])) {
    $is_update = true;
    $current_id = $_GET['updateid'];
    
    // Fetch the record to update
    $sql = "SELECT * FROM `mooncontent` WHERE id=$current_id";
    $result = mysqli_query($conn, $sql);
    
    if($result && mysqli_num_rows($result) > 0) {
        $row = mysqli_fetch_assoc($result);
        $name = $row['firstname'];
        $secondname = $row['secondname'];
        $phone = $row['phone'];
        $textcontent = $row['textcontent'];
    } else {
        $msg = "Record not found!";
        $is_update = false;
    }
}

if(isset($_POST['submit'])){
    $name = mysqli_real_escape_string($conn, $_POST['firstname']);
    $secondname = mysqli_real_escape_string($conn, $_POST['secondname']);
    $phone = mysqli_real_escape_string($conn, $_POST['phone']);
    $textcontent = mysqli_real_escape_string($conn, $_POST['textcontent']);

    // Validate phone number
    if(!preg_match('/^[0-9]{10}$/', $phone)) {
        $msg = "Please enter a valid 10-digit phone number!";
    } else {
        if($is_update && isset($_POST['update_id'])) {
            // UPDATE existing record
            $update_id = mysqli_real_escape_string($conn, $_POST['update_id']);
            $update1 = "UPDATE `mooncontent` SET firstname='$name', secondname='$secondname', phone='$phone', textcontent='$textcontent' WHERE id=$update_id";
            
            if(mysqli_query($conn, $update1)){
                $msg = "Record updated successfully!";
                $is_update = false;
                // Clear form
                $name = $secondname = $phone = $textcontent = '';
            } else {
                $msg = "Error updating record: " . mysqli_error($conn);
            }
        } else {
            // INSERT new record
            // Check if user already exists
            $select1 = "SELECT * FROM `mooncontent` WHERE firstname='$name' AND phone='$phone'";
            $select_user = mysqli_query($conn, $select1);
            
            if(!$select_user) {
                $msg = "Database error: " . mysqli_error($conn);
            }
            elseif(mysqli_num_rows($select_user) > 0){
                $msg = "User already exists!";
            }
            else{
                // Insert new record
                $insert1 = "INSERT INTO mooncontent (firstname, secondname, phone, textcontent) VALUES ('$name', '$secondname', '$phone','$textcontent')";
                if(mysqli_query($conn, $insert1)){
                    $msg = "Record added successfully!";
                    // Clear form
                    $name = $secondname = $phone = $textcontent = '';
                } else {
                    $msg = "Error: " . mysqli_error($conn);
                }
            }
        }
    }
}

// Handle delete
if(isset($_GET['deleteid'])) {
    $delete_id = $_GET['deleteid'];
    $delete_sql = "DELETE FROM `mooncontent` WHERE id=$delete_id";
    if(!mysqli_query($conn, $delete_sql)) {
         $msg = "Error deleting record: " . mysqli_error($conn);
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Registration</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet"
        integrity="sha384-sRIl4kxILFvY47J16cr9ZwB07vP4J8+LH7qKQnuqkuIAvNWLzeN8tE5YBujZqJLB" crossorigin="anonymous">

    <style>
    body {
        width: 98%;
        position: relative;
    }

    .fullcotainer-fluid {
        display: flex;
        justify-content: space-around;
        flex-wrap: wrap;
    }

    .container-fluid-1 {
        width: 45%;
        min-width: 300px;
    }

    .container-fluid-2 {
        margin-top: 30px;
        width: 40%;
        min-width: 300px;
    }

    .alert {
        margin: 10px 0;
    }

    /* ScrollSpy Styles for Data View Only */
    #records-section {
        position: relative;
    }

    .nav-scrollspy {
        position: fixed;
        top: 80px;
        right: 20px;
        background: white;
        border-radius: 10px;
        box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
        padding: 15px;
        z-index: 1000;
    }

    .nav-scrollspy .nav-link {
        color: #495057;
        padding: 8px 12px;
        border-radius: 5px;
        margin: 2px 0;
    }

    .nav-scrollspy .nav-link:hover,
    .nav-scrollspy .nav-link.active {
        background-color: #007bff;
        color: white;
    }

    /* Data table sections for ScrollSpy */
    .data-section {
        padding-top: 80px;
        margin-top: -80px;
        min-height: 400px;
    }

    @media (max-width: 768px) {

        .container-fluid-1,
        .container-fluid-2 {
            width: 100%;
        }

        .nav-scrollspy {
            display: none;
        }
    }

    </style>

</head>

<body data-bs-spy="scroll" data-bs-target="#scrollspy-nav" data-bs-offset="100" tabindex="0">

    <nav class="navbar bg-body-tertiary fixed-top">
        <div class="container-fluid">
            <a class="navbar-brand">𝓶𝓸𝓸𝓷</a>
            <form class="d-flex" role="search">
                <a class="navbar-brand col-sm-4 col-md-8">Jayachandran</a>
                <button class="  btn btn-outline-success" type="button" onclick="window.location.href='phoneverification.php'">X</button>
            </form>
        </div>
    </nav>

    <!-- Display messages -->
    <?php if(!empty($msg)): ?>
    <div class="alert alert-warning alert-dismissible fade show mt-5" role="alert">
        <?php echo $msg; ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
    <?php endif; ?>

    <div class="fullcotainer-fluid mt-5 pt-3">

        <!-- Records Section with ScrollSpy Points -->
        <div class="container-fluid-1 mt-3" id="records-section">

            <!-- Recent Records Section -->
            <div class="data-section" id="all-data">
                <div class="table-responsive" style="max-height: 500px; overflow-y: auto;">
                    <table class="table table-hover">
                        <thead class="table-primary sticky-top">
                            <tr>
                                <th scope="col">Name</th>
                                <th scope="col">பெயர்</th>
                                <th scope="col">Phone</th>
                                <th scope="col">Input</th>
                                <th scope="col">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="table-group-divider">
                            <?php 
                            $sql = "SELECT * FROM `mooncontent` ORDER BY reg_date DESC";
                            $result = mysqli_query($conn, $sql);
                            if($result && mysqli_num_rows($result) > 0){
                                while($row = mysqli_fetch_assoc($result)){
                                    echo '
                                    <tr>
                                        <td>'.htmlspecialchars($row['firstname']).'</td>
                                        <td>'.htmlspecialchars($row['secondname']).'</td>
                                        <td>'.htmlspecialchars($row['phone']).'</td>
                                        <td>'.htmlspecialchars($row['textcontent']).'</td>
                                        <td class="w-100">
                                            <button type="button" class="btn btn-primary btn-sm m-1">
                                                <a href="update.php?updateid='.$row['id'].'" class="text-white text-decoration-none">Update</a>
                                            </button>
                                            <button type="button" class="btn btn-danger btn-sm m-1">
                                                <a href="update.php?deleteid='.$row['id'].'" class="text-white text-decoration-none" onclick="return confirm(\'Are you sure you want to delete this record?\')">Delete</a>
                                            </button>
                                        </td>
                                    </tr>';
                                }
                            } else {
                                echo '<tr><td colspan="5" class="text-center">No records found</td></tr>';
                            }
                            ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="container-fluid-2">
            <form method="post">
                <?php if($is_update): ?>
                    <input type="hidden" name="update_id" value="<?php echo $current_id; ?>">
                    <div class="alert alert-info">
                        <strong>Update Mode:</strong> You are updating an existing record.
                    </div>
                <?php endif; ?>
                
                <div class="mb-3">
                    <input type="text" class="form-control" id="thanglishInput" name="firstname"
                        value="<?php echo htmlspecialchars($name); ?>"
                        placeholder='Name' required>
                </div>
                <div class="position-relative">
                    <div class="mb-2">
                        <button type="button" class="btn btn-outline-secondary position-absolute top-0 end-0"
                            onclick="convertToTamil()">Convert to Tamil</button>
                    </div>
                </div>
                <div class="mb-3 mt-3">
                    <input type="text" class="form-control" id="result" placeholder="பெயர் தமிழில்" name="secondname"
                        value="<?php echo htmlspecialchars($secondname); ?>">
                </div>

                <div class="mb-3">
                    <div class="input-group">
                        <span class="input-group-text" id="basic-addon1">+91</span>
                        <input type="tel" class="form-control" id="phone" name="phone"
                            value="<?php echo htmlspecialchars($phone); ?>"
                            placeholder="Phone No" aria-label="Phone" aria-describedby="basic-addon1"
                            pattern="[0-9]{10}" required>
                    </div>
                </div>

                <div class="mb-3">
                    <label for="textcontent" class="form-label">Comments</label>
                    <textarea class="form-control" placeholder="Leave a comment here" name="textcontent"
                        id="textcontent"
                        style="height: 100px"><?php echo htmlspecialchars($textcontent); ?></textarea>
                </div>

                <div class="input-group mb-3 mt-3">
                    <button type="reset" class="btn btn-secondary w-50" onclick="clearForm()">Clear</button>
                    <?php if($is_update): ?>
                        <button type="submit" name="submit" class="btn btn-warning w-25">Update Record</button>
                        <a href="update.php" class="btn btn-outline-secondary w-25">Cancel</a>
                    <?php else: ?>
                        <button type="submit" name="submit" class="btn btn-primary w-50">Submit New Record</button>
                    <?php endif; ?>
                </div>
            </form>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"
        integrity="sha384-FKyoEForCGlyvwx9Hj09JcMn3nv7wiPVlz7YYwJrWVcXK/BmnVDxM+D2scQbITxI" crossorigin="anonymous">
    </script>

    <script>
    // Initialize ScrollSpy for data view only
    var scrollSpy = new bootstrap.ScrollSpy(document.body, {
        target: '#scrollspy-nav'
    });

    async function convertToTamil() {
        const input = document.getElementById('thanglishInput').value.trim();
        const resultInput = document.getElementById('result');

        if (!input) {
            alert('Please enter some text to convert.');
            return;
        }

        try {
            const url =
                `https://inputtools.google.com/request?text=${encodeURIComponent(input)}&itc=ta-t-i0-und&num=1&cp=0&cs=1&ie=utf-8&oe=utf-8&app=demopage`;
            const response = await fetch(url);
            const data = await response.json();

            if (data[0] === 'SUCCESS') {
                const tamilText = data[1][0][1][0];
                resultInput.value = tamilText;
            } else {
                alert('Conversion failed. Please try again.');
            }
        } catch (error) {
            console.error('Conversion error:', error);
            alert('Error during conversion. Please check your connection.');
        }
    }

    function clearForm() {
        // Clear all form fields
        document.getElementById('thanglishInput').value = '';
        document.getElementById('result').value = '';
        document.getElementById('phone').value = '';
        document.getElementById('textcontent').value = '';
        
        // If we're in update mode, redirect to clear the updateid from URL
        if(window.location.href.includes('updateid')) {
            window.location.href = 'update.php';
        }
    }

    // Smooth scrolling for ScrollSpy links
    document.addEventListener('DOMContentLoaded', function() {
        const spyLinks = document.querySelectorAll('#scrollspy-nav .nav-link');

        spyLinks.forEach(link => {
            link.addEventListener('click', function(e) {
                e.preventDefault();

                const targetId = this.getAttribute('href');
                const targetElement = document.querySelector(targetId);

                if (targetElement) {
                    const offsetTop = targetElement.offsetTop - 80;
                    window.scrollTo({
                        top: offsetTop,
                        behavior: 'smooth'
                    });
                }
            });
        });
    });
    </script>
</body>

</html>