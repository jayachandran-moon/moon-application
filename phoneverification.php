<?php

include 'connect.php';
session_start();

// Function to generate OTP
function generateOTP($length = 6) {
    $otp = '';
    for ($i = 0; $i < $length; $i++) {
        $otp .= mt_rand(0, 9);
    }
    return $otp;
}

// Check if phone number is already registered
function isPhoneRegistered($phone, $conn) {
    $stmt = $conn->prepare("SELECT id FROM mooncontent WHERE phone = ?");
    $stmt->bind_param("s", $phone);
    $stmt->execute();
    $result = $stmt->get_result();
    return $result->num_rows > 0;
}

// Handle OTP sending
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['send_otp'])) {
    $phone = filter_var($_POST['phone'], FILTER_SANITIZE_STRING);
    
    // Validate phone number
    if (empty($phone) || !preg_match('/^[0-9]{10}$/', $phone)) {
        $_SESSION['error'] = "Please enter a valid 10-digit phone number";
    } else {
        // Check if phone is already registered
        if (isPhoneRegistered($phone, $conn)) {
            // Generate OTP only if phone is registered
            $otp = generateOTP();
            $expiration_time = date('Y-m-d H:i:s', strtotime('+10 minutes'));
            
            // Store OTP in session
            $_SESSION['otp_data'] = [
                'phone' => $phone,
                'otp_code' => $otp,
                'expires_at' => $expiration_time,
                'attempts' => 0
            ];
            
            // In real application, send OTP via SMS service here
            // For demo purposes, we'll just store it and show it
            
            $_SESSION['success'] = "OTP sent to +91$phone. Demo OTP: $otp (Valid for 10 minutes)";
            $_SESSION['phone'] = $phone;
        } else {
            $_SESSION['error'] = "This phone number is not registered. Please use a registered number or contact support.";
            unset($_SESSION['phone']);
        }
    }
    // Stay on the same page for OTP input
    header("Location: ".$_SERVER['PHP_SELF']);
    exit();
}

// Handle OTP verification
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['verify_otp'])) {
    $submitted_otp = filter_var($_POST['otp_input'], FILTER_SANITIZE_STRING);
    
    if (!isset($_SESSION['otp_data'])) {
        $_SESSION['error'] = "No OTP found. Please request a new OTP.";
    } else {
        $otp_data = $_SESSION['otp_data'];
        
        // Check if OTP is expired
        if (strtotime($otp_data['expires_at']) < time()) {
            $_SESSION['error'] = "OTP has expired. Please request a new one.";
            unset($_SESSION['otp_data']);
            unset($_SESSION['phone']);
        } 
        // Check if too many attempts
        elseif ($otp_data['attempts'] >= 3) {
            $_SESSION['error'] = "Too many failed attempts. Please request a new OTP.";
            unset($_SESSION['otp_data']);
            unset($_SESSION['phone']);
        }
        // Verify OTP
        elseif ($submitted_otp === $otp_data['otp_code']) {
            
            // Double check if phone is still registered
            if (!isPhoneRegistered($otp_data['phone'], $conn)) {
                $_SESSION['error'] = "This phone number is not registered. Please contact support.";
                unset($_SESSION['otp_data']);
                unset($_SESSION['phone']);
            } else {
                $_SESSION['success'] = "Phone number verified successfully!";
                
                // Store verified phone in session for further processing
                $verified_phone = $otp_data['phone'];
                
                // In real application, you would now:
                // 1. Update user's login status
                // 2. Mark OTP as used in database
                // 3. Set user session
                
                unset($_SESSION['otp_data']);
                unset($_SESSION['phone']);
                
                // Redirect after successful verification
                $_SESSION['verified_phone'] = $verified_phone;
                header("Location: index.php");
                exit();
            }
        } else {
            // Increment attempts
            $_SESSION['otp_data']['attempts']++;
            $remaining_attempts = 3 - $_SESSION['otp_data']['attempts'];
            $_SESSION['error'] = "Invalid OTP. $remaining_attempts attempts remaining.";
        }
    }
    // Stay on same page for error cases
    header("Location: ".$_SERVER['PHP_SELF']);
    exit();
}

// Handle reset - clear all session data
if (isset($_GET['reset'])) {
    unset($_SESSION['otp_data']);
    unset($_SESSION['phone']);
    unset($_SESSION['error']);
    unset($_SESSION['success']);
    header("Location: ".$_SERVER['PHP_SELF']);
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Phone Number Verification</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        .verification-card {
            background: white;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
            overflow: hidden;
            width: 100%;
            max-width: 450px;
        }
        .card-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 25px;
            text-align: center;
        }
        .card-body {
            padding: 30px;
        }
        .form-control {
            border-radius: 8px;
            padding: 12px 15px;
        }
        .btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border: none;
            border-radius: 8px;
            padding: 12px;
            font-weight: 600;
        }
        .btn-secondary {
            background: #6c757d;
            border: none;
            border-radius: 8px;
            padding: 12px;
            font-weight: 600;
        }
        .otp-inputs {
            display: flex;
            justify-content: space-between;
            gap: 10px;
        }
        .otp-input {
            width: 50px;
            height: 50px;
            text-align: center;
            font-size: 20px;
            font-weight: bold;
            border: 2px solid #ddd;
            border-radius: 8px;
        }
        .otp-input:focus {
            border-color: #667eea;
            box-shadow: 0 0 5px rgba(102, 126, 234, 0.5);
        }
        .alert {
            border-radius: 8px;
            border: none;
        }
        .timer {
            text-align: center;
            color: #666;
            font-size: 14px;
            margin: 10px 0;
        }
        .hidden {
            display: none;
        }
        .change-number {
            text-align: center;
            margin-top: 15px;
        }
        .change-number a {
            color: #667eea;
            text-decoration: none;
            cursor: pointer;
        }
        .change-number a:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <div class="verification-card">
        <div class="card-header">
            <h3 class="mb-0">Phone Verification</h3>
        </div>
        
        <div class="card-body">
            <!-- Display Messages -->
            <?php if (isset($_SESSION['success'])): ?>
                <div class="alert alert-success">
                    <?= $_SESSION['success']; unset($_SESSION['success']); ?>
                </div>
            <?php endif; ?>
            
            <?php if (isset($_SESSION['error'])): ?>
                <div class="alert alert-danger">
                    <?= $_SESSION['error']; unset($_SESSION['error']); ?>
                </div>
            <?php endif; ?>

            <!-- Phone Input Form -->
            <div id="phoneForm" <?= isset($_SESSION['phone']) ? 'class="hidden"' : ''; ?>>
                <form method="POST" action="">
                    <div class="mb-3">
                        <label for="phone" class="form-label">Phone Number</label>
                        <div class="input-group">
                            <span class="input-group-text">+91</span>
                            <input type="tel" class="form-control" id="phone" name="phone" 
                                   pattern="[0-9]{10}" 
                                   value="<?= isset($_SESSION['phone']) ? htmlspecialchars($_SESSION['phone']) : '' ?>" 
                                   required
                                   placeholder="Enter 10-digit phone number">
                        </div>
                        <div class="form-text">We'll send a 6-digit verification code to registered numbers</div>
                    </div>
                    <button type="submit" name="send_otp" class="btn btn-primary w-100">Send OTP</button>
                </form>
            </div>

            <!-- OTP Verification Form -->
            <div id="otpForm" <?= !isset($_SESSION['phone']) ? 'class="hidden"' : ''; ?>>
                <?php if (isset($_SESSION['phone'])): ?>
                    <p class="text-center mb-4">
                        We've sent a verification code to<br>
                        <strong>+91<?= htmlspecialchars($_SESSION['phone']) ?></strong>
                    </p>
                <?php endif; ?>
                
                <form method="POST" action="">
                    <div class="mb-3">
                        <label class="form-label">Enter 6-digit OTP</label>
                        <div class="otp-inputs">
                            <?php for ($i = 1; $i <= 6; $i++): ?>
                                <input type="text" class="otp-input" name="otp[]" maxlength="1" 
                                       oninput="moveToNext(this, <?= $i ?>)" 
                                       onkeypress="return isNumberKey(event)" 
                                       required>
                            <?php endfor; ?>
                        </div>
                        <input type="hidden" name="otp_input" id="otpInput">
                    </div>
                    
                    <div class="timer">
                        OTP expires in 10 minutes
                    </div>
                    
                    <div class="d-grid gap-2">
                        <button type="submit" name="verify_otp" class="btn btn-primary">Verify OTP</button>
                        <button type="button" class="btn btn-secondary" onclick="resetForm()">Change Phone Number</button>
                    </div>
                </form>
                
                <div class="change-number">
                    <a onclick="resetForm()">← Use different phone number</a>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Combine OTP digits into single input
        function combineOTP() {
            const otpInputs = document.querySelectorAll('.otp-input');
            let otp = '';
            otpInputs.forEach(input => {
                otp += input.value;
            });
            document.getElementById('otpInput').value = otp;
        }

        // Move to next OTP input
        function moveToNext(current, nextIndex) {
            combineOTP();
            if (current.value.length === 1 && nextIndex < 6) {
                document.querySelectorAll('.otp-input')[nextIndex].focus();
            }
        }

        // Allow only numbers in OTP fields
        function isNumberKey(evt) {
            const charCode = (evt.which) ? evt.which : evt.keyCode;
            if (charCode > 31 && (charCode < 48 || charCode > 57)) {
                return false;
            }
            return true;
        }

        // Reset form to phone input
        function resetForm() {
            window.location.href = '<?php echo $_SERVER['PHP_SELF']; ?>?reset=true';
        }

        // Auto-focus first OTP input when OTP form is shown
        <?php if (isset($_SESSION['phone'])): ?>
            document.addEventListener('DOMContentLoaded', function() {
                document.querySelector('.otp-input').focus();
            });
        <?php endif; ?>

        // Combine OTP on form submit
        document.querySelector('form').addEventListener('submit', function() {
            combineOTP();
        });
    </script>
</body>
</html>