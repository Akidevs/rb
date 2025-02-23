<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Damage and Loss Protection</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .accent-color {
            color: #198754;
        }
        .accent-bg {
            background-color: #198754;
            color: #fff;
        }
        .header-section {
            background-color: #198754;
            color: #fff;
            padding: 20px;
            text-align: center;
            border-bottom-left-radius: 20px;
            border-bottom-right-radius: 20px;
        }
        .header-section h1 {
            font-size: 2rem;
            margin-bottom: 10px;
        }
        .header-section p {
            font-size: 1.1rem;
        }
        .header-section .icon {
            font-size: 3rem;
            margin-bottom: 10px;
        }
        .nav-tabs .nav-link.active {
            background-color: #198754;
            color: #fff;
            border-color: #198754;
        }
        .nav-tabs .nav-link {
            color: #198754;
        }
        .section-title {
            font-weight: bold;
            margin-top: 20px;
            margin-bottom: 10px;
        }
        .icon-check {
            color: #198754;
            margin-right: 8px;
        }
    </style>
</head>
<body>

    <div class="container my-5">
        <!-- Header -->
        <div class="header-section">
            <div class="icon">📱</div>
            <h1>Damage and Loss Protection</h1>
            <p>A comprehensive protection plan that covers accidental damages or losses during gadget rental transactions.</p>
        </div>

        <!-- Tabs -->
        <ul class="nav nav-tabs mt-4" id="protectionTabs" role="tablist">
            <li class="nav-item" role="presentation">
                <button class="nav-link active" id="benefits-tab" data-bs-toggle="tab" data-bs-target="#benefits" type="button" role="tab">Benefits</button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="how-to-claim-tab" data-bs-toggle="tab" data-bs-target="#how-to-claim" type="button" role="tab">How to Claim</button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="terms-tab" data-bs-toggle="tab" data-bs-target="#terms" type="button" role="tab">T&C</button>
            </li>
        </ul>

        <div class="tab-content mt-3">
            <!-- Benefits Tab -->
            <div class="tab-pane fade show active" id="benefits" role="tabpanel">
                <h3 class="section-title">Damage and Loss Protection Service Benefits</h3>
                <ul>
                    <li><i class="icon-check bi bi-check-circle-fill"></i><strong>Accidental and/or liquid damage coverage:</strong> Covers damage to eligible items due to sudden, unintentional, or unexpected events. Normal wear and tear and hidden defects are not covered.</li>
                    <li><i class="icon-check bi bi-check-circle-fill"></i><strong>Protection from theft or burglary:</strong> Covers losses due to robbery or burglary involving forced entry or violence.</li>
                    <li><i class="icon-check bi bi-check-circle-fill"></i><strong>Item replacement up to 100% coverage:</strong> Covers replacement costs up to the full value of the item.</li>
                </ul>
            </div>

            <!-- How to Claim Tab -->
            <div class="tab-pane fade" id="how-to-claim" role="tabpanel">
                <h3 class="section-title">How to Claim</h3>
                <ol>
                    <li>For customer service or policy changes, email <a href="mailto:enquiries@seainsure.com.ph" class="accent-color">enquiries@seainsure.com.ph</a>.</li>
                    <li>Visit the claims portal to submit required documents for your claim.</li>
                </ol>
                <div class="mt-4 p-3 border rounded">
                    <h5>Contact Details</h5>
                    <p>SEAINSURE GENERAL INSURANCE CO., INC.<br>
                        20F The Podium West Tower, 12 ADB Avenue, Ortigas Center, Mandaluyong City, 1550 Philippines</p>
                    <p><strong>Customer Service Hours:</strong> 9am - 6pm (Weekdays, excluding public holidays)</p>
                    <p><strong>Phone:</strong> +632 8554 9585</p>
                    <p><strong>Email:</strong> <a href="mailto:enquiries@seainsure.com.ph" class="accent-color">enquiries@seainsure.com.ph</a></p>
                </div>
            </div>

            <!-- Terms & Conditions Tab -->
            <div class="tab-pane fade" id="terms" role="tabpanel">
                <h3 class="section-title">Terms & Conditions</h3>
                <p>For full details of the insurance policy, please refer to the <a href="../owner/Final_Damage_and_Loss_Policy.pdf" target="_blank" class="accent-color">Policy Wording</a>.</p>
                <p><strong>Note:</strong> This policy is underwritten by SeaInsure General Insurance Co., Inc.</p>
            </div>
        </div>

        <!-- FAQ Section -->
        <div class="mt-5">
            <h3 class="section-title">Frequently Asked Questions</h3>
            <div>
                <p><strong>Q1:</strong> How much is the price of the Damage and Loss Protection Service?</p>
                <p>The price varies depending on the value of the gadget. It can be viewed at the checkout page.</p>
            </div>
            <div>
                <p><strong>Q2:</strong> What if I lose my confirmation of coverage?</p>
                <p>You can retrieve your policy via the My Policies page in the portal or contact customer service for assistance.</p>
            </div>
            <div>
                <p><strong>Q3:</strong> Can I cancel the service and get a refund?</p>
                <p>No, the service cannot be cancelled after the completion of the rental transaction.</p>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
