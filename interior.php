<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Before/After Image Slider</title>
    <style>
        /* Container for the images */
        .container {
            position: relative;
            width: 80%;
            max-width: 600px;
            margin: auto;
            overflow: hidden;
        }

        /* Style for the "Before" and "After" images */
        .img-before, .img-after {
            position: absolute;
            width: 100%;
            height: 100%;
            transition: width 0.2s ease-out; /* Smooth transition for width */
        }

        /* Style for the slider */
        .slider {
            position: absolute;
            top: 0;
            left: 50%; /* Initially set at the center */
            width: 8px; /* Increased width of the slider for better visibility */
            height: 100%;
            background-color: rgba(0, 0, 0, 0.5); /* Slider color */
            cursor: ew-resize;
            z-index: 2;
            border-radius: 4px; /* Rounded edges for the slider */
            transition: left 0.2s ease-out; /* Smooth transition for the slider position */
        }

        /* Prevent the "after" image from overflowing */
        .img-after {
            width: 50%; /* Initially set "after" image to 50% */
        }
    </style>
</head>
<body>
    <h1>Before/After Image Comparison</h1>

    <div class="container">
        <!-- Before Image -->
        <div class="img-before">
            <img src="uploads/page/interior_before.jpeg" alt="Before Image" width="100%" height="auto">
        </div>

        <!-- After Image -->
        <div class="img-after">
            <img src="uploads/page/interior_after.jpeg" alt="After Image" width="100%" height="auto">
        </div>

        <!-- Slider -->
        <div class="slider" id="slider"></div>
    </div>

    <script>
        const slider = document.getElementById('slider');
        const imgAfter = document.querySelector('.img-after');
        const container = slider.parentElement;
        let isMouseDown = false;

        // Function to handle mouse movement
        slider.addEventListener('mousedown', (e) => {
            isMouseDown = true;
            document.addEventListener('mousemove', onMouseMove);
        });

        document.addEventListener('mouseup', () => {
            isMouseDown = false;
            document.removeEventListener('mousemove', onMouseMove);
        });

        // Handle slider movement on mouse move
        function onMouseMove(e) {
            if (!isMouseDown) return;

            // Calculate slider position within container bounds
            const containerWidth = container.offsetWidth;
            const mouseX = e.pageX - container.offsetLeft;

            // Constrain slider position
            const sliderPosition = Math.min(Math.max(mouseX, 0), containerWidth);

            // Move the slider
            slider.style.left = sliderPosition + 'px';

            // Adjust "after" image width based on slider position
            imgAfter.style.width = sliderPosition + 'px';
        }

        // Optional: For touch support (on mobile devices)
        slider.addEventListener('touchstart', (e) => {
            isMouseDown = true;
            document.addEventListener('touchmove', onTouchMove);
        });

        document.addEventListener('touchend', () => {
            isMouseDown = false;
            document.removeEventListener('touchmove', onTouchMove);
        });

        function onTouchMove(e) {
            if (!isMouseDown) return;
            const containerWidth = container.offsetWidth;
            const touchX = e.touches[0].pageX - container.offsetLeft;
            const sliderPosition = Math.min(Math.max(touchX, 0), containerWidth);

            slider.style.left = sliderPosition + 'px';
            imgAfter.style.width = sliderPosition + 'px';
        }
    </script>
</body>
</html>