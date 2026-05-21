document.addEventListener(
    'DOMContentLoaded',
    function(){

        const slider =
        document.querySelector(
            '.hero-slider'
        );

        if(!slider){
            return;
        }

        const slides =
        document.querySelectorAll(
            '.hero-slide'
        );

        const dots =
        document.querySelectorAll(
            '.hero-dot'
        );

        if(!slides.length){
            return;
        }

        let current = 0;

        let interval = null;

        /*
        |--------------------------------------------------------------------------
        | SHOW SLIDE
        |--------------------------------------------------------------------------
        */

        function showSlide(index){

            slides.forEach(slide => {

                slide.classList.remove(
                    'active-slide'
                );

            });

            dots.forEach(dot => {

                dot.classList.remove(
                    'active-dot'
                );

            });

            slides[index]
            .classList.add(
                'active-slide'
            );

            dots[index]
            .classList.add(
                'active-dot'
            );

            applyDynamicGlow(index);

            resetParallax();

            current = index;
        }

        /*
        |--------------------------------------------------------------------------
        | DYNAMIC GLOW
        |--------------------------------------------------------------------------
        */

        function applyDynamicGlow(index){

            const slide =
            slides[index];

            const primary =
            slide.dataset.primary;

            const secondary =
            slide.dataset.secondary;

            slider.style.boxShadow =
            `
            0 0 80px ${primary}55,
            0 0 140px ${secondary}22
            `;
        }

        /*
        |--------------------------------------------------------------------------
        | NEXT
        |--------------------------------------------------------------------------
        */

        function nextSlide(){

            current++;

            if(
                current >= slides.length
            ){
                current = 0;
            }

            showSlide(current);
        }

        /*
        |--------------------------------------------------------------------------
        | START
        |--------------------------------------------------------------------------
        */

        function startSlider(){

            stopSlider();

            interval =
            setInterval(
                nextSlide,
                5000
            );
        }

        /*
        |--------------------------------------------------------------------------
        | STOP
        |--------------------------------------------------------------------------
        */

        function stopSlider(){

            if(interval){

                clearInterval(interval);

                interval = null;
            }
        }

        /*
        |--------------------------------------------------------------------------
        | RESET PARALLAX
        |--------------------------------------------------------------------------
        */

        function resetParallax(){

            const activeSlide =
            document.querySelector(
                '.active-slide'
            );

            if(!activeSlide){
                return;
            }

            const bgLogo =
            activeSlide.querySelector(
                '.hero-bg-logo img'
            );

            const content =
            activeSlide.querySelector(
                '.hero-content'
            );

            if(bgLogo){

                bgLogo.style.transform =
                `
                rotate(-12deg)
                scale(1)
                `;
            }

            if(content){

                content.style.transform =
                `
                translate(0,0)
                `;
            }
        }

        /*
        |--------------------------------------------------------------------------
        | DOTS
        |--------------------------------------------------------------------------
        */

        dots.forEach((dot,index)=>{

            dot.addEventListener(
                'click',
                function(){

                    showSlide(index);

                    startSlider();
                }
            );

        });

        /*
        |--------------------------------------------------------------------------
        | HOVER
        |--------------------------------------------------------------------------
        */

        slider.addEventListener(
            'mouseenter',
            function(){

                stopSlider();
            }
        );

        slider.addEventListener(
            'mouseleave',
            function(){

                startSlider();

                resetParallax();
            }
        );

        /*
        |--------------------------------------------------------------------------
        | PARALLAX
        |--------------------------------------------------------------------------
        */

        slider.addEventListener(
            'mousemove',
            function(e){

                const x =
                (
                    window.innerWidth / 2
                    - e.pageX
                ) / 40;

                const y =
                (
                    window.innerHeight / 2
                    - e.pageY
                ) / 40;

                const activeSlide =
                document.querySelector(
                    '.active-slide'
                );

                if(!activeSlide){
                    return;
                }

                const bgLogo =
                activeSlide.querySelector(
                    '.hero-bg-logo img'
                );

                const content =
                activeSlide.querySelector(
                    '.hero-content'
                );

                if(bgLogo){

                    bgLogo.style.transform =
                    `
                    translate(${x}px, ${y}px)
                    rotate(-12deg)
                    scale(1.03)
                    `;
                }

                if(content){

                    content.style.transform =
                    `
                    translate(${x / 4}px, ${y / 4}px)
                    `;
                }
            }
        );

        /*
        |--------------------------------------------------------------------------
        | MOBILE SWIPE SUPPORT
        |--------------------------------------------------------------------------
        */

        let touchStartX = 0;

        let touchEndX = 0;

        slider.addEventListener(
            'touchstart',
            function(e){

                touchStartX =
                e.changedTouches[0].screenX;
            }
        );

        slider.addEventListener(
            'touchend',
            function(e){

                touchEndX =
                e.changedTouches[0].screenX;

                handleSwipe();
            }
        );

        function handleSwipe(){

            const diff =
            touchStartX - touchEndX;

            if(Math.abs(diff) < 50){
                return;
            }

            if(diff > 0){

                current++;

                if(current >= slides.length){
                    current = 0;
                }
            }
            else{

                current--;

                if(current < 0){
                    current =
                    slides.length - 1;
                }
            }

            showSlide(current);

            startSlider();
        }

        /*
        |--------------------------------------------------------------------------
        | KEYBOARD NAVIGATION
        |--------------------------------------------------------------------------
        */

        document.addEventListener(
            'keydown',
            function(e){

                if(e.key === 'ArrowRight'){

                    current++;

                    if(current >= slides.length){
                        current = 0;
                    }

                    showSlide(current);

                    startSlider();
                }

                if(e.key === 'ArrowLeft'){

                    current--;

                    if(current < 0){
                        current =
                        slides.length - 1;
                    }

                    showSlide(current);

                    startSlider();
                }
            }
        );

        /*
        |--------------------------------------------------------------------------
        | INIT
        |--------------------------------------------------------------------------
        */

        showSlide(0);

        startSlider();
    }
);