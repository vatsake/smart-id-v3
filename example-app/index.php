<html>

<head>
    <title>Smart ID</title>
    <script src="https://cdn.jsdelivr.net/npm/qrcodejs@1.0.0/qrcode.min.js"></script>
    <script src="https://code.jquery.com/jquery-4.0.0.js"></script>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif;
            background: #fafafa;
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 20px;
        }

        body.mobile .hide-on-mobile {
            display: none;
        }

        body:not(.mobile) .hide-on-desktop {
            display: none;
        }

        .container {
            background: white;
            border: 1px solid #e0e0e0;
            padding: 60px 40px;
            max-width: 600px;
            width: 100%;
        }

        .view {
            display: none;
        }

        .view.active {
            display: block;
        }

        h1 {
            text-align: center;
            color: #000;
            margin-bottom: 60px;
            font-size: 24px;
            font-weight: 400;
        }

        .options-grid {
            display: grid;
            gap: 50px;
        }

        .category {
            margin-bottom: 0;
        }

        .category-title {
            font-size: 12px;
            font-weight: 600;
            color: #666;
            margin-bottom: 20px;
            text-transform: uppercase;
            letter-spacing: 1.5px;
        }

        .sub-options {
            display: grid;
            gap: 10px;
        }

        .option-btn {
            padding: 18px 24px;
            border: 1px solid #d0d0d0;
            background: white;
            cursor: pointer;
            font-size: 15px;
            font-weight: 400;
            color: #000;
            transition: all 0.2s ease;
            text-align: left;
        }

        .option-btn:hover {
            border-color: #000;
            background: #fafafa;
        }

        .option-btn:active {
            background: #f0f0f0;
        }

        /* QR View Styles */
        .qr-view {
            text-align: center;
        }

        .action-label {
            color: #666;
            font-weight: 600;
            margin-bottom: 30px;
            text-transform: uppercase;
            font-size: 11px;
            letter-spacing: 1.5px;
        }

        #qrcode {
            display: flex;
            justify-content: center;
            margin-bottom: 40px;
        }

        .back-btn {
            margin-top: 20px;
            padding: 12px 24px;
            background: white;
            color: #000;
            border: 1px solid #d0d0d0;
            cursor: pointer;
            font-size: 14px;
            font-weight: 400;
            transition: all 0.2s ease;
        }

        .back-btn:hover {
            background: #fafafa;
            border-color: #000;
        }

        .back-btn:active {
            background: #f0f0f0;
        }

        /* ID Code Input Styles */
        .id-input-view {
            text-align: center;
        }

        .input-group {
            margin-bottom: 30px;
        }

        .input-label {
            display: block;
            font-size: 12px;
            font-weight: 600;
            color: #666;
            text-transform: uppercase;
            letter-spacing: 1.5px;
            margin-bottom: 12px;
        }

        .id-input {
            width: 100%;
            padding: 14px 18px;
            border: 1px solid #d0d0d0;
            font-size: 15px;
            font-family: inherit;
            transition: all 0.2s ease;
        }

        .id-input:focus {
            outline: none;
            border-color: #000;
        }

        .submit-btn {
            width: 100%;
            padding: 14px 24px;
            background: #000;
            color: white;
            border: 1px solid #000;
            cursor: pointer;
            font-size: 15px;
            font-weight: 400;
            transition: all 0.2s ease;
        }

        .submit-btn:hover {
            background: #333;
        }

        .submit-btn:active {
            background: #000;
        }

        .submit-btn:disabled {
            background: #e0e0e0;
            color: #999;
            border-color: #e0e0e0;
            cursor: not-allowed;
        }
    </style>
</head>

<body>
    <div class="container">
        <div id="selectionView" class="view active">
            <div class="options-grid">
                <div class="category">
                    <div class="category-title">Notification</div>
                    <div class="sub-options">
                        <button class="option-btn" onclick="selectAction('notification_sign')">Sign</button>
                        <button class="option-btn" onclick="selectAction('notification_auth')">Authenticate</button>
                    </div>
                </div>
                <div class="category">
                    <div class="category-title">Device Link</div>
                    <div class="sub-options">
                        <button class="option-btn" onclick="selectAction('device_link_sign')">Sign <b class="hide-on-desktop">(Doesn't work on mobile)</b></button>
                        <button class="option-btn" onclick="selectAction('device_link_auth')">Authenticate</button>
                    </div>
                </div>
            </div>
        </div>

        <!-- ID Code Input -->
        <div id="idInputView" class="view id-input-view">
            <h1>Enter Your ID Code</h1>
            <div class="action-label" id="idActionLabel"></div>
            <div class="input-group">
                <label class="input-label" for="semIdentifierInput">National ID Code</label>
                <input type="text" id="semIdentifierInput" class="id-input" placeholder="e.g., PNOEE-30303039914" onkeypress="if(event.key === 'Enter') submitIdCode()" />
            </div>
            <p style="margin-bottom: 20px">For example in DEMO: <b>PNOEE-40504040001</b></p>
            <button class="submit-btn" onclick="submitIdCode()">Continue</button>
            <button class="back-btn" onclick="goBack()">← Go Back</button>
        </div>

        <!-- QR -->
        <div id="qrView" class="view qr-view">
            <h1 id="qrViewTitle">Scan with Smart ID App</h1>
            <div class="action-label" id="actionLabel"></div>
            <div id="qrcode"></div>
            <div id="signed-by"></div>
            <div id="end-result"></div>
            <div id="verification-result"></div>
            <button class="back-btn" onclick="goBack()">← Go Back</button>
        </div>
    </div>

    <script type="text/javascript">
        let qrcode = null;
        let done = false;
        let currentAction = null;
        let qrPollRequest = null;
        let statusPollRequest = null;
        let qrPollTimeout = null;
        let statusPollTimeout = null;
        let semIdentifier = null;



        $(document).ready(function() {
            const queryParams = getQueryParams();
            const hasResults = queryParams.action && (queryParams.signer || queryParams.endResult !== undefined);

            $('body').toggleClass('mobile', isMobile());

            if (hasResults) {
                // User was redirected back with callback results
                displayResults(queryParams);
                window.history.replaceState({}, document.title, window.location.pathname);
            }
        });

        function displayResults(params) {
            currentAction = params.action;
            done = true;

            $('#actionLabel').text(getActionLabel(currentAction));
            $('#qrcode').hide();
            showView('qrView');

            if (params.signer) {
                $('#signed-by').html('Signed by: <b>' + decodeURIComponent(params.signer) + '</b>');
            }
            if (params.endResult) {
                $('#end-result').html('End result: <b>' + decodeURIComponent(params.endResult) + '</b>');
            }
            if (params.verificationResult !== undefined) {
                const verResult = params.verificationResult === 'true';
                $('#verification-result').html('Verification: <b>' + (verResult ? 'Successful' : 'Failed') + '</b>');
            }
        }

        function getQueryParams() {
            const params = new URLSearchParams(window.location.search);
            const allParams = {};
            params.forEach((value, key) => {
                allParams[key] = value;
            });
            return allParams;
        }

        function showView(viewId) {
            $('.view').removeClass('active');
            $('#' + viewId).addClass('active');
        }

        function getActionLabel(action) {
            const labels = {
                'notification_sign': 'Sign via Notification',
                'notification_auth': 'Auth via Notification',
                'device_link_sign': 'Sign via Device Link',
                'device_link_auth': 'Auth via Device Link',
            };
            return labels[action];
        }

        function isMobile() {
            const ua = navigator.userAgent
            return /mobi|iphone|ipod|android.*mobile/.test(ua) || 'ontouchend' in document;
        }

        function selectAction(action) {
            currentAction = action;
            done = false;
            semIdentifier = null;

            if (action.startsWith('notification')) {
                // Show ID input for notification flows
                $('#idActionLabel').text(getActionLabel(action));
                $('#semIdentifierInput').val('');
                showView('idInputView');
            } else {
                // Show QR view for device link flows
                initializeQRCode();
                $('#actionLabel').text(getActionLabel(action));
                $('#qrViewTitle').text('Scan with Smart ID App');
                showView('qrView');

                startSession(action);
            }
        }

        function initializeQRCode() {
            if (!qrcode) {
                qrcode = new QRCode("qrcode", {
                    width: 512,
                    height: 512,
                    colorDark: "#000000",
                    colorLight: "#ffffff",
                    correctLevel: QRCode.CorrectLevel.L
                });
            } else {
                qrcode.clear();
            }
            $('#qrcode').show();
        }

        function submitIdCode() {
            const idCode = $('#semIdentifierInput').val().trim();

            if (!idCode) {
                alert('Please enter your ID code');
                return;
            }

            semIdentifier = idCode;
            showNotificationWaitView();
            startSession(currentAction);
        }

        function showNotificationWaitView() {
            $('#actionLabel').text(getActionLabel(currentAction));
            $('#qrcode').hide();
            $('#qrViewTitle').text('Check Your Phone');
            showView('qrView');
        }

        function goBack() {
            done = true;
            cancelAllRequests();
            clearAllTimeouts();
            cleanupQRCode();
            resetUIState();
            showView('selectionView');
        }

        function cancelAllRequests() {
            if (qrPollRequest) {
                qrPollRequest.abort();
                qrPollRequest = null;
            }
            if (statusPollRequest) {
                statusPollRequest.abort();
                statusPollRequest = null;
            }
        }

        function clearAllTimeouts() {
            if (qrPollTimeout) {
                clearTimeout(qrPollTimeout);
                qrPollTimeout = null;
            }
            if (statusPollTimeout) {
                clearTimeout(statusPollTimeout);
                statusPollTimeout = null;
            }
        }

        function cleanupQRCode() {
            if (qrcode) {
                qrcode.clear();
            }
        }

        function resetUIState() {
            currentAction = null;
            semIdentifier = null;
            $('#semIdentifierInput').val('');
            $('#signed-by').text('');
            $('#end-result').text('');
            $('#verification-result').text('');
        }

        function startSession(action) {
            let url = '/start-session.php?action=' + action;

            if (semIdentifier) {
                url += '&semIdentifier=' + encodeURIComponent(semIdentifier);
            }

            // Add QR flag for device link flows
            if (action.startsWith('device_link')) {
                url += '&qr=' + !isMobile();
            }

            $.ajax({
                url: url,
                method: 'GET',
                contentType: 'application/json',
                success: function(response) {
                    handleSessionStart(response, action);
                },
                error: function(error) {
                    alert(error.responseText);
                    goBack();
                }
            });
        }

        function handleSessionStart(response, action) {
            // For device link flows on mobile, redirect to device link
            if (action.startsWith('device_link') && isMobile()) {
                const resp = typeof response === 'string' ? JSON.parse(response) : response;
                window.location.href = resp.link;
                return;
            }

            // Start polling for device link QR or notification flows
            if (action.startsWith('device_link')) {
                startQrPolling(action);
            }

            startStatusPolling();
        }

        function startQrPolling(action) {
            if (done) return;

            qrPollRequest = $.ajax({
                url: '/qr.php?action=' + action,
                method: 'GET',
                contentType: 'application/json',
                success: function(response) {
                    const resp = typeof response === 'string' ? JSON.parse(response) : response;
                    qrcode.makeCode(resp.link);

                    if (!done) {
                        qrPollTimeout = setTimeout(() => {
                            qrcode.clear();
                            startQrPolling(action);
                        }, 1000);
                    }
                },
                error: function(error) {
                    if (error.statusText === 'abort') return;
                    alert(error.responseText);
                    goBack();
                }
            });
        }

        function startStatusPolling() {
            if (done) return;

            let url = '/status.php';
            const queryParams = getQueryParams();
            const queryString = Object.keys(queryParams)
                .map(key => encodeURIComponent(key) + '=' + encodeURIComponent(queryParams[key]))
                .join('&');

            if (queryString) {
                url += '?' + queryString;
            }

            statusPollRequest = $.ajax({
                url: url,
                method: 'GET',
                contentType: 'application/json',
                success: function(response) {
                    const resp = typeof response === 'string' ? JSON.parse(response) : response;

                    if (resp.state === 'COMPLETE') {
                        done = true;
                        showResults(resp);
                    } else if (!done) {
                        statusPollTimeout = setTimeout(startStatusPolling, 1000);
                    }
                },
                error: function(error) {
                    if (error.statusText === 'abort') return;
                    alert(error.responseText);
                    goBack();
                }
            });
        }

        function showResults(resp) {
            if (resp.signer) {
                $('#signed-by').html('Signed by: <b>' + resp.signer + '</b>');
            }
            if (resp.endResult) {
                $('#end-result').html('End result: <b>' + resp.endResult + '</b>');
            }
            if (resp.verificationResult !== null) {
                $('#verification-result').html('Verification: <b>' + (resp.verificationResult ? 'Successful' : 'Failed') + '</b>');
            }
        }
    </script>

</body>

</html>