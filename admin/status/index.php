<?php
require_once '../../assets/database/auth.php';
require_once '../../assets/database/config.php';

checkSessionTimeout();

// Check if the user is logged in and is an admin
if (!isLoggedIn() || getUser()['role'] !== 'admin') {
    header("Location: ../auth/login/");
    exit();
}
$currentUser = getUser();

$conn = getDatabaseConnection();

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Update order status
    if (isset($_POST['update_status'])) {
        $order_id = $_POST['order_id'];
        $new_status = $_POST['status'];
        $notes = $_POST['notes'] ?? '';
        $notify_customer = isset($_POST['notify_customer']) ? 1 : 0;
        
        // Update order status
        $stmt = $conn->prepare("UPDATE Orders SET status = ? WHERE order_id = ?");
        $stmt->bind_param("si", $new_status, $order_id);
        
        if ($stmt->execute()) {
            // Log activity
            $activity_stmt = $conn->prepare("INSERT INTO UserActivities (user_id, activity_type) VALUES (?, 'order_status_update')");
            $activity_stmt->bind_param("i", $currentUser['user_id']);
            $activity_stmt->execute();
            
            $_SESSION['success_message'] = "Order status updated successfully!";
            
            // In a real application, you would send email notification here if $notify_customer is true
        } else {
            $_SESSION['error_message'] = "Error updating order status: " . $conn->error;
        }
    }
    
    // Generate invoice
    if (isset($_POST['generate_invoice'])) {
        $order_id = $_POST['order_id'];
        $format = $_POST['format'];
        $email = $_POST['email'] ?? '';
        $send_copy = isset($_POST['send_copy']) ? 1 : 0;
        $include_tracking = isset($_POST['include_tracking']) ? 1 : 0;
        
        // Get order details
        $order_stmt = $conn->prepare("SELECT * FROM Orders WHERE order_id = ?");
        $order_stmt->bind_param("i", $order_id);
        $order_stmt->execute();
        $order = $order_stmt->get_result()->fetch_assoc();
        
        if ($order) {
            // Log activity
            $activity_stmt = $conn->prepare("INSERT INTO UserActivities (user_id, activity_type) VALUES (?, 'invoice_generated')");
            $activity_stmt->bind_param("i", $currentUser['user_id']);
            $activity_stmt->execute();
            
            $_SESSION['success_message'] = "Invoice generated for order #$order_id";
            
            // In a real application, you would generate the invoice file or email here
        } else {
            $_SESSION['error_message'] = "Order not found!";
        }
    }
    
    header("Location: " . $_SERVER['PHP_SELF']);
    exit();
}

// Get all orders with user and item details
$orders = [];
$stmt = $conn->prepare("
    SELECT 
        o.order_id, 
        o.order_date, 
        o.total_amount, 
        o.status, 
        u.user_id, 
        u.username, 
        u.email,
        u.first_name,
        u.last_name,
        COUNT(oi.order_item_id) AS item_count
    FROM Orders o
    JOIN Users u ON o.user_id = u.user_id
    LEFT JOIN OrderItems oi ON o.order_id = oi.order_id
    GROUP BY o.order_id
    ORDER BY o.order_date DESC
");
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $orders[] = $row;
}

// Get order items for each order
foreach ($orders as &$order) {
    $stmt = $conn->prepare("
        SELECT 
            oi.*, 
            g.title, 
            g.cover_image,
            g.actual_price,
            g.discounted_price
        FROM OrderItems oi
        JOIN Games g ON oi.game_id = g.game_id
        WHERE oi.order_id = ?
    ");
    $stmt->bind_param("i", $order['order_id']);
    $stmt->execute();
    $items_result = $stmt->get_result();
    $order['items'] = $items_result->fetch_all(MYSQLI_ASSOC);
}
unset($order); // Break the reference
?>

<!DOCTYPE html>
<html lang="en">

<head>
   <meta name="viewport" content="width=device-width, initial-scale=1.0">
   <title>Admin Games - Duarcade</title>
   <meta name="description" content="Duarcade - Your ultimate gaming destination. Explore a wide range of games, connect with fellow gamers, and enjoy an immersive gaming experience.">
   <meta name="keywords" content="Duarcade, gaming, games, online games, multiplayer, arcade games">
   <meta name="author" content="Duarcade Team">
   <meta name="theme-color" content="#000000">
   <link rel="icon" href="images/favicon.ico" type="image/x-icon">
   <!-- Bootstrap 5 CSS -->
   <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-1BmE4kWBq78iYhFldvKuhfTAU6auU8tT94WrHftjDbrCEXSU1oBoqyl2QvZ6jIW3" crossorigin="anonymous">

   <!-- Font Awesome 6 -->
   <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" rel="stylesheet">
   <!-- Bootstrap 5 JS -->
   <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-ka7Sk0Gln4gmtz2MlQnikT1wXgY9F8c7Jpaj6x5I/Cbm+IbbVYUew+OrCXaRkfj" crossorigin="anonymous"></script>
   <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
   <!--=============== REMIXICONS ===============-->
   <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/remixicon/4.2.0/remixicon.css">
   <!--=============== CSS ===============-->
   <link rel="stylesheet" href="../../assets/css/admin.css">
   <style>
      .order-card {
         border-radius: 10px;
         box-shadow: 0 4px 8px rgba(0,0,0,0.1);
         margin-bottom: 20px;
         overflow: hidden;
         transition: transform 0.3s ease;
      }
      .order-card:hover {
         transform: translateY(-5px);
      }
      .order-header {
         background-color: #f8f9fa;
         padding: 15px;
         border-bottom: 1px solid #eee;
      }
      .order-body {
         padding: 15px;
      }
      .order-item {
         display: flex;
         justify-content: space-between;
         padding: 8px 0;
         border-bottom: 1px solid #f1f1f1;
      }
      .status-badge {
         padding: 5px 10px;
         border-radius: 20px;
         font-size: 0.8rem;
         font-weight: 600;
      }
      .status-pending {
         background-color: #fff3cd;
         color: #856404;
      }
      .status-processing {
         background-color: #cfe2ff;
         color: #084298;
      }
      .status-completed {
         background-color: #d1e7dd;
         color: #0a3622;
      }
      .status-cancelled {
         background-color: #f8d7da;
         color: #58151c;
      }
      .accordion-button:not(.collapsed) {
         background-color: rgba(0,123,255,.1);
         color: #0d6efd;
      }
      .invoice-options {
         background-color: #f8f9fa;
         padding: 15px;
         border-radius: 8px;
         margin-top: 15px;
      }
      .game-image {
         width: 60px;
         height: 80px;
         object-fit: cover;
         border-radius: 4px;
      }
   </style>
</head>

<body>
   <!--=============== HEADER ===============-->
   <header class="header" id="header">
      <div class="header__container">
         <a href="#" class="header__logo">
            <i class="ri-cloud-fill"></i>
            <span>Cloud</span>
         </a>

         <button class="header__toggle" id="header-toggle">
            <i class="ri-menu-line"></i>
         </button>
      </div>
   </header>

   <!--=============== SIDEBAR ===============-->
   <nav class="sidebar" id="sidebar">
      <div class="sidebar__container">
         <div class="sidebar__user">
            <div class="sidebar__img">
                    <img src="../../assets/img/profile/<?php echo htmlspecialchars($currentUser['profile_image'] ? 'user_' . $currentUser['user_id'] . '.jpg' : 'default.png'); ?>" alt="Profile Image">
   </div>

            <div class="sidebar__info">
               <h3><?php echo htmlspecialchars($currentUser['first_name'] . ' ' . $currentUser['last_name']); ?></h3>
               <span><?php echo htmlspecialchars($currentUser['email'] ?? 'user@example.com'); ?></span>
            </div>
         </div>

         <div class="sidebar__content">
            <div>
               <div class="sidebar__list">
                  <a href="../../public/" class="sidebar__link">
                     <i class="ri-home-3-line"></i>
                     <span>Home</span>
                  </a>
               </div>
            </div>

            <div>
               <h3 class="sidebar__title">MANAGE</h3>
               <div class="sidebar__list">
                  <a href="../../admin/user/" class="sidebar__link">
                     <i class="ri-user-line"></i>
                     <span>Users</span>
                  </a>
               </div>
            </div>

            <div>
               <h3 class="sidebar__title">Store</h3>
               <div class="sidebar__list">
                  <a href="../../admin/game/" class="sidebar__link">
                     <i class="ri-gamepad-line"></i>
                     <span>Games</span>
                  </a>
                  <a href="../../admin/status/" class="sidebar__link active-link">
                     <i class="ri-file-list-3-line"></i>
                     <span>Orders</span>
                  </a>
               </div>
            </div>
         </div>

         <div class="sidebar__actions">
            <button>
               <i class="ri-moon-clear-line sidebar__link sidebar__theme" id="theme-button">
                  <span>Theme</span>
               </i>
            </button>

            <a href="../../auth/logout/" class="sidebar__link">
               <i class="ri-logout-circle-r-line"></i>
               <span>Log Out</span>
            </a>
         </div>
      </div>
   </nav>

   <!--=============== MAIN ===============-->
   <main class="main container" id="main">
      <h1 class="my-4">Order Management</h1>
      
      <?php if (isset($_SESSION['success_message'])): ?>
         <div class="alert alert-success alert-dismissible fade show" role="alert">
            <?php echo htmlspecialchars($_SESSION['success_message']); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
         </div>
         <?php unset($_SESSION['success_message']); ?>
      <?php endif; ?>

      <?php if (isset($_SESSION['error_message'])): ?>
         <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <?php echo htmlspecialchars($_SESSION['error_message']); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
         </div>
         <?php unset($_SESSION['error_message']); ?>
      <?php endif; ?>
      
      <!-- Order Summary Dropdown -->
      <div class="accordion mb-4" id="orderAccordion">
         <div class="accordion-item">
            <h2 class="accordion-header">
               <button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#orderSummary" aria-expanded="true" aria-controls="orderSummary">
                  <i class="ri-file-list-3-line me-2"></i> Order Summary
               </button>
            </h2>
            <div id="orderSummary" class="accordion-collapse collapse show" data-bs-parent="#orderAccordion">
               <div class="accordion-body">
                  <?php if (empty($orders)): ?>
                     <div class="alert alert-info">
                        No orders found.
                     </div>
                  <?php else: ?>
                     <?php foreach ($orders as $order): ?>
                        <div class="order-card">
                           <div class="order-header">
                              <div class="d-flex justify-content-between align-items-center">
                                 <h5>Order #ORD-<?php echo $order['order_id']; ?></h5>
                                 <span class="status-badge status-<?php echo $order['status']; ?>">
                                    <?php echo ucfirst($order['status']); ?>
                                 </span>
                              </div>
                              <div class="d-flex justify-content-between mt-2">
                                 <small class="text-muted">
                                    Customer: <?php echo htmlspecialchars($order['first_name'] . ' ' . $order['last_name']); ?>
                                 </small>
                                 <small class="text-muted">
                                    Placed: <?php echo date('M j, Y', strtotime($order['order_date'])); ?>
                                 </small>
                              </div>
                           </div>
                           <div class="order-body">
                              <?php foreach ($order['items'] as $item): ?>
                                 <div class="order-item">
                                    <div class="d-flex align-items-center">
                                       <?php if ($item['cover_image']): ?>
                                          <img src="../../assets/img/games/<?php echo htmlspecialchars($item['cover_image']); ?>" 
                                               alt="<?php echo htmlspecialchars($item['title']); ?>" 
                                               class="game-image me-3">
                                       <?php endif; ?>
                                       <div>
                                          <div><?php echo htmlspecialchars($item['title']); ?></div>
                                          <small class="text-muted">
                                             <?php echo '$' . number_format($item['discounted_price'] ?? $item['actual_price'], 2); ?>
                                          </small>
                                       </div>
                                    </div>
                                    <span><?php echo $item['quantity']; ?> × $<?php echo number_format($item['price_at_purchase'], 2); ?></span>
                                 </div>
                              <?php endforeach; ?>
                              
                              <div class="order-item">
                                 <span><strong>Subtotal</strong></span>
                                 <span>$<?php echo number_format($order['total_amount'], 2); ?></span>
                              </div>
                              <div class="order-item">
                                 <span>Shipping</span>
                                 <span>$0.00</span>
                              </div>
                              <div class="order-item">
                                 <span><strong>Total</strong></span>
                                 <span><strong>$<?php echo number_format($order['total_amount'], 2); ?></strong></span>
                              </div>
                           </div>
                        </div>
                     <?php endforeach; ?>
                  <?php endif; ?>
               </div>
            </div>
         </div>
      </div>
      
      <!-- Update Order Status Dropdown -->
      <div class="accordion mb-4" id="updateAccordion">
         <div class="accordion-item">
            <h2 class="accordion-header">
               <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#updateOrder" aria-expanded="false" aria-controls="updateOrder">
                  <i class="ri-edit-2-line me-2"></i> Update Order Status
               </button>
            </h2>
            <div id="updateOrder" class="accordion-collapse collapse" data-bs-parent="#updateAccordion">
               <div class="accordion-body">
                  <form method="post" id="updateOrderForm">
                     <div class="mb-3">
                        <label for="orderSelect" class="form-label">Select Order</label>
                        <select class="form-select" id="orderSelect" name="order_id" required>
                           <option value="">Choose an order</option>
                           <?php foreach ($orders as $order): ?>
                              <option value="<?php echo $order['order_id']; ?>">
                                 #ORD-<?php echo $order['order_id']; ?> - <?php echo ucfirst($order['status']); ?>
                              </option>
                           <?php endforeach; ?>
                        </select>
                     </div>
                     <div class="mb-3">
                        <label for="statusSelect" class="form-label">New Status</label>
                        <select class="form-select" id="statusSelect" name="status" required>
                           <option value="">Select new status</option>
                           <option value="pending">Pending</option>
                           <option value="completed">Completed</option>
                           <option value="cancelled">Cancelled</option>
                        </select>
                     </div>
                     <div class="mb-3">
                        <label for="statusNotes" class="form-label">Admin Notes</label>
                        <textarea class="form-control" id="statusNotes" name="notes" rows="3" placeholder="Add any notes about this status change..."></textarea>
                     </div>
                     <div class="form-check mb-3">
                        <input class="form-check-input" type="checkbox" id="notifyCustomer" name="notify_customer" checked>
                        <label class="form-check-label" for="notifyCustomer">
                           Notify customer via email
                        </label>
                     </div>
                     <button type="submit" name="update_status" class="btn btn-primary w-100">
                        <i class="ri-save-line me-1"></i> Update Status
                     </button>
                  </form>
               </div>
            </div>
         </div>
      </div>
      
      <!-- Generate Invoice Dropdown -->
      <div class="accordion" id="invoiceAccordion">
         <div class="accordion-item">
            <h2 class="accordion-header">
               <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#generateInvoice" aria-expanded="false" aria-controls="generateInvoice">
                  <i class="ri-file-download-line me-2"></i> Generate Invoice
               </button>
            </h2>
            <div id="generateInvoice" class="accordion-collapse collapse" data-bs-parent="#invoiceAccordion">
               <div class="accordion-body">
                  <form method="post" id="invoiceForm">
                     <div class="mb-3">
                        <label for="invoiceOrderSelect" class="form-label">Select Order</label>
                        <select class="form-select" id="invoiceOrderSelect" name="order_id" required>
                           <option value="">Choose an order</option>
                           <?php foreach ($orders as $order): ?>
                              <option value="<?php echo $order['order_id']; ?>">
                                 #ORD-<?php echo $order['order_id']; ?> - <?php echo ucfirst($order['status']); ?>
                              </option>
                           <?php endforeach; ?>
                        </select>
                     </div>
                     
                     <div class="invoice-options">
                        <h6 class="mb-3">Invoice Options</h6>
                        <div class="mb-3">
                           <label for="invoiceFormat" class="form-label">Format</label>
                           <select class="form-select" id="invoiceFormat" name="format" required>
                              <option value="pdf">PDF Document</option>
                              <option value="html">HTML Web Page</option>
                              <option value="email">Email to Customer</option>
                           </select>
                        </div>
                        
                        <div id="emailOptions" style="display: none;">
                           <div class="mb-3">
                              <label for="customerEmail" class="form-label">Customer Email</label>
                              <input type="email" class="form-control" id="customerEmail" name="email" placeholder="customer@example.com">
                           </div>
                           <div class="form-check mb-3">
                              <input class="form-check-input" type="checkbox" id="sendCopy" name="send_copy" checked>
                              <label class="form-check-label" for="sendCopy">
                                 Send copy to admin
                              </label>
                           </div>
                           <div class="form-check">
                              <input class="form-check-input" type="checkbox" id="includeTracking" name="include_tracking">
                              <label class="form-check-label" for="includeTracking">
                                 Include tracking information (if available)
                              </label>
                           </div>
                        </div>
                     </div>
                     
                     <button type="submit" name="generate_invoice" class="btn btn-success w-100 mt-3">
                        <i class="ri-download-line me-1"></i> Generate Invoice
                     </button>
                  </form>
               </div>
            </div>
         </div>
      </div>
   </main>

   <!--=============== MAIN JS ===============-->
   <script src="../../assets/js/admin.js"></script>
   <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
   <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
   <script>
      // Show/hide email options based on format selection
      document.getElementById('invoiceFormat').addEventListener('change', function() {
         const emailOptions = document.getElementById('emailOptions');
         emailOptions.style.display = this.value === 'email' ? 'block' : 'none';
      });
      
      // Pre-fill email when order is selected
      document.getElementById('invoiceOrderSelect').addEventListener('change', function() {
         const selectedOption = this.options[this.selectedIndex];
         if (selectedOption.value && selectedOption.dataset.email) {
            document.getElementById('customerEmail').value = selectedOption.dataset.email;
         }
      });
      
      // Set up data attributes for email pre-fill
      const orders = <?php echo json_encode($orders); ?>;
      const invoiceOrderSelect = document.getElementById('invoiceOrderSelect');
      orders.forEach(order => {
         const option = invoiceOrderSelect.querySelector(`option[value="${order.order_id}"]`);
         if (option) {
            option.dataset.email = order.email;
         }
      });
   </script>
</body>
</html>