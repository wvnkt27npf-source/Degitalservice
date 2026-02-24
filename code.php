<?php
// Sample PHP code to render a page similar to the provided application UI

// Helper function to generate navigation buttons
function navButton($label, $active = false) {
    $class = $active ? 'nav-button active' : 'nav-button';
    echo "&lt;button class=\"$class\"&gt;$label&lt;/button&gt;";
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Power Data Recovery</title>
    <style>
        /* Reset some default */
        body, html {
            margin:0; padding:0; font-family: Arial, sans-serif; background: #c9d6e6;
        }
        .container {
            width: 700px;
            margin: 30px auto;
            background: #e9f0f9;
            border: 1px solid #a3b9da;
            box-shadow: 0 0 8px #a3b9da;
            border-radius: 8px;
            overflow: hidden;
        }
        .header {
            position: relative;
            background: linear-gradient(to bottom, #ffffff 0%, #97b2da 100%);
            height: 110px;
        }
        .header h1 {
            font-size: 26px;
            color: #15346d;
            font-weight: bold;
            margin: 18px 0 0 150px;
            letter-spacing: -1px;
        }
        .header .sub-title {
            margin-left: 150px;
            color: #7999cb;
            font-size: 12px;
            letter-spacing: 1px;
        }
        .header .icon {
            position: absolute;
            top: 12px;
            left: 15px;
            width: 120px;
            height: 104px;
            background:
                url('https://placehold.co/117x100?text=Hard+Disk+Icon&font=arial&color=515151&bg=FFFFFF') no-repeat center center;
            background-size: contain;
            border-radius: 8px;
            filter: drop-shadow(2px 2px 2px #aaa);
        }
        .nav {
            background: linear-gradient(to bottom, #7d9adf 0%, #3252a8 100%);
            padding: 4px 12px;
            display: flex;
            gap: 10px;
            border-bottom: 1px solid #16306f;
        }
        .nav-button {
            background: #accbff;
            border: 1px solid #3a62a8;
            color: #15346d;
            font-weight: bold;
            cursor: pointer;
            padding: 5px 20px;
            border-radius: 5px 5px 0 0;
            transition: background 0.3s;
            user-select: none;
        }
        .nav-button.active {
            background: #8aad2f;
            color: #3c3c2a;
            border-color: #789928 #789928 #689119;
        }
        .nav-button:hover:not(.active) {
            background: #b7d0ff;
        }
        .content {
            padding: 18px 32px 32px 32px;
            font-size: 14px;
            color: #15346d;
        }
        .instruction {
            text-align: center;
            margin-bottom: 14px;
            font-weight: bold;
            color: #21418f;
            font-size: 15px;
        }
        .modules-box {
            border: 1px solid #6e8dcb;
            background: #dae4f4;
            border-radius: 8px 8px 8px 8px;
            padding: 15px 20px;
            display: flex;
            justify-content: space-around;
        }
        .module {
            width: 140px;
            background: #c9d6ec;
            border-radius: 6px;
            padding: 14px;
            box-shadow: inset 1px 1px 4px #d1dbf3, inset -1px -1px 3px #9caed4;
            text-align: center;
            cursor: default;
            user-select: none;
            transition: background 0.3s;
        }
        .module:hover {
            background: #aabde9;
        }
        .module img {
            width: 68px;
            height: 68px;
            margin-bottom: 8px;
            filter: drop-shadow(1px 1px 1px #9999a3);
        }
        .module-text {
            color: #224296;
            font-weight: bold;
            line-height: 1.2em;
            font-size: 13px;
            user-select: none;
        }
        .module-text small {
            display: block;
            font-weight: normal;
            font-size: 11px;
            margin-top: 3px;
            color: #4466aa;
        }
        .info-box {
            margin-top: 25px;
            border: 1px solid #9aafcb;
            background: #dbe2ee;
            border-radius: 8px;
            padding: 15px 20px;
            font-size: 13px;
            box-shadow: inset 0 1px 5px #f9faff;
            color: #32508d;
        }
        .info-box strong {
            color: #15347b;
        }
        .info-box a {
            color: #0049c7;
            text-decoration: none;
        }
        .info-box a:hover {
            text-decoration: underline;
        }
        .footer {
            margin-top: 20px;
            text-align: center;
            font-size: 11px;
            color: #7584a6;
            padding: 12px 0;
            background: linear-gradient(to bottom, #cbd0db 0%, #9aabd8 100%);
            border-top: 1px solid #7182a8;
            user-select: none;
        }
        /* Magnifying glass image on right bottom */
        .magnifier {
            position: absolute;
            bottom: -8px;
            right: 16px;
            width: 110px;
            height: 110px;
            opacity: 0.15;
            filter: drop-shadow(0 0 1px #a1b0c9);
            pointer-events: none;
            user-select:none;
            background:
                url('https://placehold.co/110x110?text=Magnifying+Glass+Icon&font=arial&color=999999&bg=FFFFFF') no-repeat center center;
            background-size: contain;
            z-index: 0;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <div class="icon" aria-hidden="true"></div>
            <h1>Power Data Recovery</h1>
            <div class="sub-title">www.PowerDataRecovery.com</div>
        </div>
        <nav class="nav" role="navigation" aria-label="Main navigation">
            <?php navButton("Home", true); ?>
            <?php navButton("Recover"); ?>
            <?php navButton("Case Study"); ?>
            <?php navButton("Register/Buy"); ?>
        </nav>
        <main class="content" role="main">
            <p class="instruction" aria-live="polite">Please choose a recovery module to start:</p>
            <section class="modules-box" aria-label="Recovery Modules">
                <div class="module" tabindex="0" role="button" aria-pressed="false" aria-label="Undelete Recovery">
                    <img src="https://placehold.co/68x68?text=Recycle+Bin+Icon&font=arial&color=555555&bg=bdc7e7" alt="Icon of a recycling bin with paper inside, gray and blue on light background" onerror="this.style.display='none'"/>
                    <div class="module-text">
                        Undelete <br /><small>Recovery</small>
                    </div>
                </div>
                <div class="module" tabindex="0" role="button" aria-pressed="false" aria-label="Advanced Recovery">
                    <img src="https://placehold.co/68x68?text=Magnifying+Glass+Hard+Drive&font=arial&color=555555&bg=bdc7e7" alt="Icon showing a magnifying glass examining a hard drive, gray and blue tones on light background" onerror="this.style.display='none'"/>
                    <div class="module-text">
                        Advanced <br /><small>Recovery</small>
                    </div>
                </div>
                <div class="module" tabindex="0" role="button" aria-pressed="false" aria-label="Deep Scan Recovery">
                    <img src="https://placehold.co/68x68?text=Monitor+Magnifying+Glass&font=arial&color=555555&bg=bdc7e7" alt="Icon of a desktop monitor with a magnifying glass in front, gray and blue on light background" onerror="this.style.display='none'"/>
                    <div class="module-text">
                        Deep Scan <br /><small>Recovery</small>
                    </div>
                </div>
                <div class="module" tabindex="0" role="button" aria-pressed="false" aria-label="Resume Recovery">
                    <img src="https://placehold.co/68x68?text=Clock+Hard+Drive&font=arial&color=555555&bg=bdc7e7" alt="Icon of a clock positioned on a hard drive, symbolizes resume recovery, gray and blue tones" onerror="this.style.display='none'"/>
                    <div class="module-text">
                        Resume <br /><small>Recovery</small>
                    </div>
                </div>
            </section>

            <section class="info-box" aria-label="License Information">
                <div style="display: flex; align-items: center; margin-bottom: 14px;">
                    <div style="width: 64px; height: 64px; border-radius: 12px; background: radial-gradient(circle at center, #9fc981, #689737); box-shadow: inset 0 2px 6px #b8d58e, 0 4px 6px #547a26; display: flex; align-items: center; justify-content: center; margin-right: 16px;">
                        <img src="https://placehold.co/48x48?text=Upgrade+Icon&font=arial&color=ffffff&bg=597f2b" alt="Circular icon with concentric rings symbolizing upgrade" onerror="this.style.display='none'" />
                    </div>
                    <div style="font-size: 12px; color: #27513a; font-weight: bold;">
                        Upgrade
                    </div>
                </div>
                <div style="font-size: 13px; line-height: 1.5em;">
                    Power Data Recovery (version: 4.1.2)<br />
                    License Type: <strong style="color: red;">Commercial License</strong><br />
                    Web Site: <a href="https://www.powerdatarecovery.com" target="_blank" rel="noopener noreferrer">www.powerdatarecovery.com</a><br />
                    Tech. Support: <a href="mailto:support@powerdatarecovery.com">support@powerdatarecovery.com</a>
                </div>
            </section>
        </main>
        <div class="footer" role="contentinfo">
            Copyright (C) 2005-2008 IT Software, All rights reserved.
        </div>
        <div class="magnifier" aria-hidden="true" ></div>
    </div>
</body>
</html>

