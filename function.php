<?php
//function.php

function fill_category_list($connect)
{
	$query = "
	SELECT * FROM category 
	WHERE category_status = 'active' 
	ORDER BY category_name ASC
	";
	$statement = $connect->prepare($query);
	$statement->execute();
	$result = $statement->fetchAll();
	$output = '';
	foreach ($result as $row) {
		$output .= '<option value="' . $row["category_id"] . '">' . $row["category_name"] . '</option>';
	}
	return $output;
}

function fill_brand_list($connect, $category_id)
{
	$query = "SELECT * FROM brand 
	WHERE brand_status = 'active' 
	AND category_id = '" . $category_id . "'
	ORDER BY brand_name ASC";
	$statement = $connect->prepare($query);
	$statement->execute();
	$result = $statement->fetchAll();
	$output = '<option value="">Select Brand</option>';
	foreach ($result as $row) {
		$output .= '<option value="' . $row["brand_id"] . '">' . $row["brand_name"] . '</option>';
	}
	return $output;
}

function get_user_name($connect, $user_id)
{
	$query = "
	SELECT user_name FROM user_details WHERE user_id = '" . $user_id . "'
	";
	$statement = $connect->prepare($query);
	$statement->execute();
	$result = $statement->fetchAll();
	foreach ($result as $row) {
		return $row['user_name'];
	}
}

function fill_product_list($connect)
{
	$query = "
	SELECT * FROM product 
	WHERE product_status = 'active' 
	ORDER BY product_name ASC
	";
	$statement = $connect->prepare($query);
	$statement->execute();
	$result = $statement->fetchAll();
	$output = '';
	foreach ($result as $row) {
		$output .= '<option value="' . $row["product_id"] . '">' . $row["product_name"] . '</option>';
	}
	return $output;
}

function fetch_product_details($product_id, $connect)
{
	$query = "
	SELECT * FROM product 
	WHERE product_id = '" . $product_id . "'";
	$statement = $connect->prepare($query);
	$statement->execute();
	$result = $statement->fetchAll();
	foreach ($result as $row) {
		$output['product_name'] = $row["product_name"];
		$output['quantity'] = $row["product_quantity"];
		$output['price'] = $row['product_base_price'];
		$output['tax'] = $row['product_tax'];
	}
	return $output;
}

function available_product_quantity($connect, $product_id)
{
	$product_data = fetch_product_details($product_id, $connect);
	$query = "
	SELECT 	inventory_order_product.quantity FROM inventory_order_product 
	INNER JOIN inventory_order ON inventory_order.inventory_order_id = inventory_order_product.inventory_order_id
	WHERE inventory_order_product.product_id = '" . $product_id . "' AND
	inventory_order.inventory_order_status = 'active'
	";
	$statement = $connect->prepare($query);
	$statement->execute();
	$result = $statement->fetchAll();
	$total = 0;
	foreach ($result as $row) {
		$total = $total + $row['quantity'];
	}
	$available_quantity = intval($product_data['quantity']) - intval($total);
	if ($available_quantity == 0) {
		$update_query = "
		UPDATE product SET 
		product_status = 'inactive' 
		WHERE product_id = '" . $product_id . "'
		";
		$statement = $connect->prepare($update_query);
		$statement->execute();
	}
	return $available_quantity;
}

function count_total_user($connect)
{
	$query = "
	SELECT * FROM user_details WHERE user_status='active'";
	$statement = $connect->prepare($query);
	$statement->execute();
	$result =  mysqli_stmt_get_result($statement);
	return mysqli_num_rows($result);
}

function count_total_category($connect)
{
	$query = "
	SELECT * FROM category WHERE category_status='active'
	";
	$statement = $connect->prepare($query);
	$statement->execute();
	$result =  mysqli_stmt_get_result($statement);
	return mysqli_num_rows($result);
}

function count_total_brand($connect)
{
	$query = "
	SELECT * FROM brand WHERE brand_status='active'
	";
	$statement = $connect->prepare($query);
	$statement->execute();
	$result =  mysqli_stmt_get_result($statement);
	return mysqli_num_rows($result);
}

function count_total_product($connect)
{
	$query = "
	SELECT * FROM product WHERE product_status='active'
	";
	$statement = $connect->prepare($query);
	$statement->execute();
	$result =  mysqli_stmt_get_result($statement);
	return mysqli_num_rows($result);
}

function count_total_order_value($connect)
{
	$query = "
	SELECT sum(inventory_order_total) as total_order_value FROM inventory_order 
	WHERE inventory_order_status='active'
	";
	if ($_SESSION['type'] == 'user') {
		$query .= ' AND user_id = "' . $_SESSION["user_id"] . '"';
	}
	$statement = $connect->prepare($query);
	$statement->execute();

	$result =  mysqli_stmt_get_result($statement);
	foreach ($result as $row) {
		return number_format($row['total_order_value'], 2);
	}
}

function count_total_cash_order_value($connect)
{
	$query = "
	SELECT sum(inventory_order_total) as total_order_value FROM inventory_order 
	WHERE payment_status = 'cash' 
	AND inventory_order_status='active'
	";
	if ($_SESSION['type'] == 'user') {
		$query .= ' AND user_id = "' . $_SESSION["user_id"] . '"';
	}
	$statement = $connect->prepare($query);
	$statement->execute();
	$result = mysqli_stmt_get_result($statement);
	foreach ($result as $row) {
		return number_format($row['total_order_value'], 2);
	}
}

function count_total_credit_order_value($connect)
{
	$query = "
	SELECT sum(inventory_order_total) as total_order_value FROM inventory_order WHERE payment_status = 'credit' AND inventory_order_status='active'
	";
	if ($_SESSION['type'] == 'user') {
		$query .= ' AND user_id = "' . $_SESSION["user_id"] . '"';
	}
	$statement = $connect->prepare($query);
	$statement->execute();
	$result = mysqli_stmt_get_result($statement);
	foreach ($result as $row) {
		return number_format($row['total_order_value'], 2);
	}
}

function get_user_wise_total_order($connect)
{
	$query = '
	SELECT sum(inventory_order.inventory_order_total) as order_total, 
	SUM(CASE WHEN inventory_order.payment_status = "cash" THEN inventory_order.inventory_order_total ELSE 0 END) AS cash_order_total, 
	SUM(CASE WHEN inventory_order.payment_status = "cradit" THEN inventory_order.inventory_order_total ELSE 0 END) AS credit_order_total, 
	user_details.user_name 
	FROM inventory_order 
	INNER JOIN user_details ON user_details.user_id = inventory_order.user_id 
	WHERE inventory_order.inventory_order_status = "active" GROUP BY inventory_order.user_id
	';
	$statement = $connect->prepare($query);
	$statement->execute();
	$result =  mysqli_stmt_get_result($statement);
	$output = '
	<div class="table-responsive">
		<table class="table table-bordered table-striped">
			<tr>
				<th>User Name</th>
				<th>Total Order Value</th>
				<th>Total Cash Order</th>
				<th>Total Credit Order</th>
			</tr>
	';

	$total_order = 0;
	$total_cash_order = 0;
	$total_credit_order = 0;
	foreach ($result as $row) {
		$output .= '
		<tr>
			<td>' . $row['user_name'] . '</td>
			<td align="right">$ ' . $row["order_total"] . '</td>
			<td align="right">$ ' . $row["cash_order_total"] . '</td>
			<td align="right">$ ' . $row["credit_order_total"] . '</td>
		</tr>
		';

		$total_order = $total_order + $row["order_total"];
		$total_cash_order = $total_cash_order + $row["cash_order_total"];
		$total_credit_order = $total_credit_order + $row["credit_order_total"];
	}
	$output .= '
	<tr>
		<td align="right"><b>Total</b></td>
		<td align="right"><b>$ ' . $total_order . '</b></td>
		<td align="right"><b>$ ' . $total_cash_order . '</b></td>
		<td align="right"><b>$ ' . $total_credit_order . '</b></td>
	</tr></table></div>
	';
	return $output;
}

function get_order($connect)
{
	$query = '
	SELECT inventory_order_id, 
	inventory_order_total,
	payment_status,
	inventory_order_status,
	inventory_order_date,
	inventory_order_name,
	user_details.user_name
	FROM inventory_order 
	INNER JOIN user_details ON user_details.user_id = inventory_order.user_id 
	GROUP BY inventory_order.user_id
	';
	$statement = $connect->prepare($query);
	$statement->execute();
	$result =  mysqli_stmt_get_result($statement);
	$output = '';

	foreach ($result as $row) {
		$dateTime = DateTime::createFromFormat("Y-m-d", $row["inventory_order_date"]);
		$resultDate = $dateTime->format("d F Y");
		$output .= '
		<tr>
			<td>' . $row['inventory_order_id'] . '</td>
			<td align="right"> ' . $row["user_name"] . '</td>
			<td align="right">$ ' . $row["inventory_order_total"] . '</td>
			<td align="right"> ' . $row["payment_status"] . '</td>
			<td align="right"> ' . $row["inventory_order_status"] . '</td>
			<td align="right"> ' . $resultDate . '</td>
			<td align="right"> ' . $row["inventory_order_name"] . '</td>
			<td align="right"></td>
		</tr>
		';
	}

	return $output;
}

function get_products($connect)
{
	$query = '
	SELECT
	product_id,
	category.category_name,
	brand.brand_name,
	product_name,
	product_quantity,
	product_enter_by,
	product_status
	FROM product 
	INNER JOIN category ON category.category_id = product.category_id 
	INNER JOIN brand ON brand.brand_id = product.brand_id
	';
	$statement = $connect->prepare($query);
	$statement->execute();
	$result =  mysqli_stmt_get_result($statement);
	$output = '';

	foreach ($result as $row) {
		$output .= '
		<tr>
			<td>' . $row['product_id'] . '</td>
			<td>' . $row['category_name'] . '</td>
			<td>' . $row['brand_name'] . '</td>
			<td>' . $row['product_name'] . '</td>
			<td>' . $row['product_quantity'] . '</td>
			<td>' . $row['product_enter_by'] . '</td>
			<td>' . $row['product_status'] . '</td>
		</tr>
		';
	}

	return $output;
}

function get_brands($connect)
{
	$query = '
	SELECT
	brand_id,
	category.category_name,
	brand_name,
	brand_status
	FROM brand 
	INNER JOIN category ON category.category_id = brand.category_id 
	';
	$statement = $connect->prepare($query);
	$statement->execute();
	$result =  mysqli_stmt_get_result($statement);
	$output = '';

	foreach ($result as $row) {
		$output .= '
		<tr>
			<td>' . $row['brand_id'] . '</td>
			<td>' . $row['category_name'] . '</td>
			<td>' . $row['brand_name'] . '</td>
			<td>' . $row['brand_status'] . '</td>
			<td><button>Edit</button></td>
			<td><button class="delete" id=' . $row['brand_id'] . ' data-status=' . $row['brand_status'] . '>Delete</button></td>
		</tr>
		';
	}

	return $output;
}

function get_categories($connect)
{
	$query = '
	SELECT
	category_id,
	category_name,
	category_status
	FROM category 
	';
	$statement = $connect->prepare($query);
	$statement->execute();
	$result =  mysqli_stmt_get_result($statement);
	$output = '';

	foreach ($result as $row) {
		$output .= '
		<tr>
			<td>' . $row['category_id'] . '</td>
			<td>' . $row['category_name'] . '</td>
			<td>' . $row['category_status'] . '</td>
			<td><button>Edit</button></td>
			<td><button>Delete</button></td>
		</tr>
		';
	}

	return $output;
}

function get_users($connect)
{
	$query = '
	SELECT
	user_id,
	user_email,
	user_name,
	user_status
	FROM user_details 
	';
	$statement = $connect->prepare($query);
	$statement->execute();
	$result =  mysqli_stmt_get_result($statement);
	$output = '';

	foreach ($result as $row) {
		$output .= '
		<tr>
			<td>' . $row['user_id'] . '</td>
			<td>' . $row['user_email'] . '</td>
			<td>' . $row['user_name'] . '</td>
			<td>' . $row['user_status'] . '</td>
			<td><button>Edit</button></td>
			<td><button>Delete</button></td>
		</tr>
		';
	}

	return $output;
}
