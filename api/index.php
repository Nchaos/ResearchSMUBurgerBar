<?php
require 'vendor/autoload.php';
$app = new \Slim\Slim();
$app->get('/hello/:name', function ($name) {
    echo "Hello, $name";
});

$mysqli = new mysqli("localhost", "root", "toor", "mydb");
if ($mysqli->connect_errno)
    die("Connection failed: " . $mysqli->connect_error);

$app->get('/getMeats', function () {
	$meats = '{
		"meats": [
		{
			"name": "1/3 lb. Beef",
			"price": 2
		},
		{
			"name": "1/2 lb. Beef",
			"price": 2.25
		},
		{
			"name": "2/3 lb. Beef",
			"price": 2.5
		},
		{
			"name": "Turkey",
			"price": 2
		},
		{
			"name": "Veggie",
			"price": 2
		}
		]
	}'; 

    echo json_encode(json_decode($meats, true));
});

$app->get('/getBuns', function () {
	$buns = '{"buns": [
	{
		"name": "White",
		"price": 0.50
	},
	{
		"name": "Wheat",
		"price": 0.50
	},
	{
		"name": "Texas Toast",
		"price": 0.75
	}
	] } ';
    echo json_encode(json_decode($buns, true));
});

$app->get('/getCheeses', function () {
	$cheeses =  '{ "cheeses": [
	{
		"name": "Cheddar",
		"price": 0.35
	},
	{
		"name": "American",
		"price": 0.35
	},
	{
		"name": "Swiss",
		"price": 0.35
	}
	] }';
    echo json_encode(json_decode($cheeses, true));
});

$app->get('/getToppings', function () {
    $toppings =  '{ "toppings": [
			{
                "name": "Tomatoes",
                "price": 0
            },
			{
                "name": "Lettuce",
                "price": 0
            },
            {
                "name": "Onions",
                "price": 0
            },
			{
                "name": "Pickles",
                "price": 0
            },
			{
				"name": "Bacon",
				"price": 1
			},
			{
                "name": "Red Onion",
                "price": 0
            },
            {
                "name": "Mushrooms",
                "price": 0
            },
			{
                "name": "Jalapenos",
                "price": 0
            }
        ] }';
       echo json_encode(json_decode($toppings, true));

});

$app->get('/getSauces', function () {
    $sauces =  '{"sauces": [
            {
                "name": "Ketchup",
                "price": 0
            },
            {
                "name": "Mustard",
                "price": 0
            },
            {
                "name": "Mayonnaise",
                "price": 0
            },
            {
                "name": "BBQ",
                "price": 0
            }
        ]}';
        echo json_encode(json_decode($sauces, true));

});

$app->get('/getSides', function () {
    $sides =  '{"sides": [
            {
                "name": "French Fries",
                "price": 1
            },
            {
                "name": "Tater Tots",
                "price": 1
            },
            {
                "name": "Onion Rings",
                "price": 1
            }
        ]}';
        echo json_encode(json_decode($sides, true));

});

$app->get('/getLastOrder/:userID', function ($id) { //currently untested
    global $mysqli;
    $orderQuery=$mysqli->query("SELECT idOrder FROM BurgerOrder WHERE User_idUser = '$id' ORDER BY idOrder DESC LIMIT 1");
    $largestID=$orderQuery->fetch_assoc();
    echo "largest = ".$largestID['idOrder'];
   
    //get list of burgers in the most recent order and create list of components of each burger
    $burgerSet=array();
    $burgerList=$mysqli->query("SELECT idBurger FROM Burger Where BurgerOrder_idOrder = '".$largestID['idOrder']."'");
    while(true){
        $burger=$burgerList->fetch_assoc();
        
        if($burger===NULL)
            break;
        echo "->".$burger['idBurger'];
        $compQuery="SELECT BurgerComponent_idBurgerComponent FROM Burger_has_BurgerComponent WHERE Burger_idBurger='" . $burger["idBurger"]."'";
        $compList=$mysqli->query($compQuery);
        $burgerSubset=array();
        while(true){
            $comp = $compList->fetch_assoc();
            if($comp===NULL)
                break;
            echo "(".$comp['BurgerComponent_idBurgerComponent'].")";
           array_push($burgerSubset, $comp['BurgerComponent_idBurgerComponent']);
        }
        array_push($burgerSet, $burgerSubset); 
        echo "    ";
    }
    echo "\n\n";
    echo json_encode($burgerSet);
    
    /*$burgers = array("1" => $burger1, "2" => $burger2);
    echo json_encode($burgers);*/
});

$app->post('/createUserAccount', function () {
    global $mysqli;
    $fName = $_POST['fName'];
    $lName = $_POST['lName'];
    $email = $_POST['email'];
    $password = $_POST['password'];
    $CCprovider = $_POST['ccProvider'];
    $CCNumber = $_POST['ccNumber'];
    if($fName === "" || $lName === "" || $email === "" || $password === "" || $CCprovider === "" || $CCNumber === "")
	$outputJSON = array ('u_id'=>-2);
    else{
	$dupCheck = $mysqli->query("SELECT email FROM User WHERE email = '$email' LIMIT 1");
	$checkResults = $dupCheck->fetch_assoc();
	    if(!($checkResults === NULL))
		$outputJSON = array ('u_id'=>-1);
	    else{
		$prevUser = $mysqli->query("SELECT idUser FROM User ORDER BY idUser DESC LIMIT 1");
		$row = $prevUser->fetch_assoc();
		if($row === NULL){
		    $outputJSON = array ('u_id'=>1);
		    $CCNumber = (int) $CCNumber;
		    $insertion = $mysqli->query("INSERT INTO User (idUser, fName, lName, email, password, ccProvider, ccNumber) VALUES (1, '$fName', '$lName', '$email', '$password', '$CCprovider', $CCNumber)");
		}
		else{
		    $newID = $row['idUser']+1;
		    $outputJSON = array ('u_id'=>$newID);
		    $CCNumber = (int) $CCNumber;
		    $insertion = $mysqli->query("INSERT INTO User (idUser, fName, lName, email, password, ccProvider, ccNumber) VALUES ($newID, '$fName', '$lName', '$email', '$password', '$CCprovider', $CCNumber)");
		}
            }
        }
	echo json_encode($outputJSON);
});

$app->post('/loginUser', function () {
    session_start();
    global $mysqli;
    $email = $_POST['email'];
    $password = $_POST['password'];
    try {
    $sql = "SELECT idUser FROM User WHERE email=(?)";
    $stmt = $mysqli -> prepare($sql);
    $stmt -> bind_param('s', $email);
    $stmt -> execute();
    $username_test = $stmt -> fetch();
    if(($username_test === NULL)) {
        $JSONarray = array(
            'status'=>'Failure', 
            'user_id'=>NULL,
            'fName'=>NULL,
            'lName'=>NULL,
            'email'=>NULL);
        echo json_encode($JSONarray);
        return;
    }
    else{
        $stmt->close();
        $sql = "SELECT password FROM User WHERE email=(?)";
        $stmt1 = $mysqli -> prepare($sql);
        $stmt1 -> bind_param('s', $email);
        $stmt1 -> execute();
        $passwordVal = '';
        $stmt1->bind_result($passwordVal);
        $stmt1 -> fetch();
       
        if($passwordVal === NULL) {
            $JSONarray = array(
            'status'=>'Failure', 
            'user_id'=>NULL,
            'fName'=>NULL,
            'lName'=>NULL,
            'email'=>NULL);
            echo json_encode($JSONarray);
            return;
        } 
    
        else if($password == $passwordVal) { 
            $stmt1->close();              
            $_SESSION['loggedin'] = true;
            $query = "SELECT idUser FROM User WHERE email=(?)";
            $stmt2 = $mysqli -> prepare($query);
            $stmt2 -> bind_param('s', $email);
            $stmt2 -> execute();
            $stmt2->bind_result($temp);         
            $stmt2 -> fetch();    
            $_SESSION['userId'] = $temp;
            $_SESSION['email'] = $email;    
            $statusFlg = 'Succeed';
            $stmt2->close();
            $components = "SELECT * FROM User WHERE email='$email'";
            $returnValue = $mysqli -> query($components);
            $iteration = $returnValue -> fetch_assoc();
            $JSONarray = array(
                'status'=>$statusFlg,
                'user_id'=>$iteration['idUser'],
                'firstName'=>$iteration['firstName'],
                'lastName'=>$iteration['lastName'],
                'email'=>$iteration['email']);
            echo json_encode($JSONarray);
            return;
        } 
        //verifies password
        else {
            $JSONarray = array(
                'status'=>'Failure', 
                'user_id'=>NULL,
                'fName'=>NULL,
                'lName'=>NULL,
                'email'=>NULL);
            echo json_encode($JSONarray);
            return;
        }
    }
    //returns null when password is wrong
        $mysqli = null;
    } catch(exception $e) {
        echo '{"error":{"text":'. $e->getMessage() .'}}'; 
    }
    echo "Finish5";
});
$app->post('/logout', function()  { 
    session_start();
    $_SESSION = array(); 
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 800000,
            $params["path"], $params["domain"],
            $params["secure"], $params["httponly"]
        );
    }
    session_destroy();
});


$app->post('/placeUserOrder', function () {
    global $mysqli;
    $outputJSON = array ('status'=>"Success");
    $userID = $_POST['userID'];
    $fName = $_POST['fName'];
    $lName = $_POST['lName'];
    $CCprovider = $_POST['ccProvider'];
    $CCNumber = $_POST['ccNumber'];
    $burgers = $_POST['burgers'];
    $userID = (int) $userID;

    $getOrderID = $mysqli->query("SELECT idOrder FROM BurgerOrder ORDER BY idOrder DESC LIMIT 1");
    if($getOrderID === false)
	$prevOrderID = 0;
    else
	$prevOrderID = $getOrderID->fetch_assoc();


    $getBurgerID = $mysqli->query("SELECT idBurger FROM Burger ORDER BY idBurger DESC LIMIT 1");
    if($getBurgerID === false)
	$prevBurgerID = 0;
    else
	$prevBurgerID = $getBurgerID->fetch_assoc();

    $newOrderID = (int) $prevOrderID['idOrder'] + 1;
    if(!($userID === 0)){
	$order = $mysqli->query("INSERT INTO BurgerOrder VALUES ($newOrderID,$userID)");
	$burgerList = json_decode($burgers);
	$burgerID = $prevBurgerID['idBurger'];
	foreach($burgerList as $burger)	{
	    $burgerID = (int) $burgerID+1;
	    $burger = (array) $burger;
	    $quantity = (int) $burger['quantity'];
	    $orderIDString = (string) $newOrderID;
	    $newBurger = $mysqli->query("INSERT INTO Burger VALUES ($burgerID,$quantity,'$orderIDString')");
	    
	    foreach($burger["components"] as $component){
		$getComponentID = $mysqli->query("SELECT idBurgerComponent FROM BurgerComponent WHERE ComponentName = '$component' LIMIT 1");
		if(!($getComponentID === false))
		    $componentID = $getComponentID->fetch_assoc();
		else{
		    $outputJSON = array('status'=>"Failure",'message'=>"GUI don goofed");
		    break 2;
		}
		$componentID = (int) $componentID['idBurgerComponent'];
		$newComponent = $mysqli->query("INSERT INTO Burger_has_BurgerComponent VALUES ('$burgerID','$componentID')");
		}
	    }
	}
    else
	if($fName === "" || $lName === "" || $CCprovider === "" || $CCNumber === "")
	    $outputJSON = array ('status'=>"Failure");
	
	
    
    
    echo json_encode($outputJSON);
});




$app->run();
?>

