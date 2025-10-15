<?php
include 'connect.php';
if(isset($_GET['deleteid'])){
    $id= $_GET['deleteid'];
    $sql="DELETE FROM `mooncontent`where id=$id";
    $result=mysqli_query($conn,$sql);
    if($result){
        // echo"deleetd succesfully";
        header('location:index.php');
    }
    else{
        echo"eror";
    }
}


?>