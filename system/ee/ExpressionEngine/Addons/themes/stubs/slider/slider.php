<!doctype html>
<html lang="en">
    <head>
        <meta charset="utf-8" />
        <meta name="viewport" content="width=device-width, initial-scale=1" />
        <link
            href="https://fonts.googleapis.com/css?family=Roboto:300,400,500,700,900&display=swap"
            rel="stylesheet" />
        <link
            rel="stylesheet"
            href="https://cdn.jsdelivr.net/npm/tw-elements/dist/css/tw-elements.min.css" />
        <script src="https://cdn.tailwindcss.com/3.3.0"></script>
        <script>
            tailwind.config = {
                darkMode: "class",
                theme: {
                fontFamily: {
                    sans: ["Roboto", "sans-serif"],
                    body: ["Roboto", "sans-serif"],
                    mono: ["ui-monospace", "monospace"],
                },
                },
                corePlugins: {
                preflight: false,
                },
            };
        </script>
        <title><?=$channel_title?>: {site_name}</title>
    </head>
    <body>

                    <div id="carouselExampleCaptions" class="relative" data-te-carousel-init data-te-ride="carousel">

                        <!--Carousel items-->
                        <div class="relative w-full overflow-hidden after:clear-both after:block after:content-['']">
                            {exp:channel:entries channel="<?=$channel?>" dynamic="no" limit="5"}
                            <div class="relative float-left -mr-[100%]{if count > 1} hidden{/if} w-full transition-transform duration-[600ms] ease-in-out motion-reduce:transition-none"{if count==1} data-te-carousel-active{/if} data-te-carousel-item style="backface-visibility: hidden">
                                <img src="{<?=$fields['file']['field_name']?>}" class="block w-full" alt="{title}" />
                                <div class="absolute inset-x-[15%] bottom-5 hidden py-5 text-center text-white md:block">
                                    <h5 class="text-xl">{title}</h5>
                                    <p>{<?=$fields['textarea']['field_name']?>}</p>
                                </div>
                            </div>
                            {/exp:channel:entries}
                        </div>

                        <!--Carousel controls - prev item-->
                        <button
                            class="absolute bottom-0 left-0 top-0 z-[1] flex w-[15%] items-center justify-center border-0 bg-none p-0 text-center text-white opacity-50 transition-opacity duration-150 ease-[cubic-bezier(0.25,0.1,0.25,1.0)] hover:text-white hover:no-underline hover:opacity-90 hover:outline-none focus:text-white focus:no-underline focus:opacity-90 focus:outline-none motion-reduce:transition-none"
                            type="button"
                            data-te-target="#carouselExampleCaptions"
                            data-te-slide="prev">
                            <span class="inline-block h-8 w-8">
                            <svg
                                xmlns="http://www.w3.org/2000/svg"
                                fill="none"
                                viewBox="0 0 24 24"
                                stroke-width="1.5"
                                stroke="currentColor"
                                class="h-6 w-6">
                                <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                d="M15.75 19.5L8.25 12l7.5-7.5" />
                            </svg>
                            </span>
                            <span
                            class="!absolute !-m-px !h-px !w-px !overflow-hidden !whitespace-nowrap !border-0 !p-0 ![clip:rect(0,0,0,0)]"
                            >Previous</span
                            >
                        </button>
                        <!--Carousel controls - next item-->
                        <button
                            class="absolute bottom-0 right-0 top-0 z-[1] flex w-[15%] items-center justify-center border-0 bg-none p-0 text-center text-white opacity-50 transition-opacity duration-150 ease-[cubic-bezier(0.25,0.1,0.25,1.0)] hover:text-white hover:no-underline hover:opacity-90 hover:outline-none focus:text-white focus:no-underline focus:opacity-90 focus:outline-none motion-reduce:transition-none"
                            type="button"
                            data-te-target="#carouselExampleCaptions"
                            data-te-slide="next">
                            <span class="inline-block h-8 w-8">
                            <svg
                                xmlns="http://www.w3.org/2000/svg"
                                fill="none"
                                viewBox="0 0 24 24"
                                stroke-width="1.5"
                                stroke="currentColor"
                                class="h-6 w-6">
                                <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                d="M8.25 4.5l7.5 7.5-7.5 7.5" />
                            </svg>
                            </span>
                            <span
                            class="!absolute !-m-px !h-px !w-px !overflow-hidden !whitespace-nowrap !border-0 !p-0 ![clip:rect(0,0,0,0)]"
                            >Next</span
                            >
                        </button>
                    </div>

        <script src="https://cdn.jsdelivr.net/npm/tw-elements/dist/js/tw-elements.umd.min.js"></script>
    </body>
</html>