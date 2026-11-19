<script setup lang="ts">
import { onBeforeUnmount, onMounted, ref } from 'vue';

const wibClock = new Intl.DateTimeFormat('en-GB', {
    timeZone: 'Asia/Jakarta',
    hour: '2-digit',
    minute: '2-digit',
    hour12: false,
});

const wibTime = ref('--:--');

let clockTimer: number | undefined;

function tickWibClock(): void {
    wibTime.value = wibClock.format(new Date());
}

onMounted(() => {
    tickWibClock();
    clockTimer = window.setInterval(tickWibClock, 1000);
});

onBeforeUnmount(() => {
    if (clockTimer !== undefined) {
        window.clearInterval(clockTimer);
    }
});
</script>

<template>
    <svg
        viewBox="0 0 520 570"
        fill="none"
        xmlns="http://www.w3.org/2000/svg"
        aria-hidden="true"
        class="h-auto w-full select-none"
    >
        <defs>
            <clipPath id="wm-drum-clip">
                <circle cx="260" cy="342" r="92" />
            </clipPath>
        </defs>

        <!-- Porthole echo behind the machine -->
        <circle cx="260" cy="304" r="238" class="wm-aura" />
        <circle cx="260" cy="304" r="206" class="wm-ring" />

        <!-- Ground shadow -->
        <ellipse cx="260" cy="518" rx="164" ry="14" class="wm-shadow" />

        <!-- Feet -->
        <rect x="132" y="498" width="44" height="18" rx="6" class="wm-ink" />
        <rect x="344" y="498" width="44" height="18" rx="6" class="wm-ink" />

        <!-- Clothes rack with hanging shirts and detergent -->
        <g class="wm-rack">
            <rect x="106" y="28" width="10" height="22" rx="4" class="wm-rack-mount" />
            <rect x="302" y="28" width="10" height="22" rx="4" class="wm-rack-mount" />
            <rect x="112" y="40" width="194" height="8" rx="4" class="wm-rack-bar" />

            <g class="wm-hanging">
                <path d="M148 44 V 58 L 133 70 H 163 L 148 58" class="wm-hanger" />
                <path
                    d="M135 68 L 126 78 L 133 85 L 139 79 V 100 H 157 V 79 L 163 85 L 170 78 L 161 68 Z"
                    class="wm-shirt-teal wm-line-3"
                    stroke-linejoin="round"
                />
            </g>
            <g class="wm-hanging">
                <path d="M202 44 V 58 L 187 70 H 217 L 202 58" class="wm-hanger" />
                <path
                    d="M189 68 L 180 78 L 187 85 L 193 79 V 100 H 211 V 79 L 217 85 L 224 78 L 215 68 Z"
                    class="wm-shirt-blue wm-line-3"
                    stroke-linejoin="round"
                />
            </g>
            <g class="wm-hanging">
                <path d="M256 44 V 58 L 241 70 H 271 L 256 58" class="wm-hanger" />
                <path
                    d="M243 68 L 234 78 L 241 85 L 247 79 V 100 H 265 V 79 L 271 85 L 278 78 L 269 68 Z"
                    class="wm-shirt-yellow wm-line-3"
                    stroke-linejoin="round"
                />
            </g>

            <rect x="344" y="44" width="30" height="12" rx="4" class="wm-detergent-cap wm-line-3" />
            <rect x="326" y="54" width="66" height="50" rx="8" class="wm-detergent-body wm-line-4" />
            <rect x="338" y="64" width="42" height="28" rx="4" class="wm-detergent-label" />
            <path d="M344 80 Q 352 73 360 80 T 376 80" class="wm-detergent-wave" />
            <text x="359" y="87" text-anchor="middle" class="wm-detergent-text">
                DETERJEN
            </text>
        </g>

        <!-- Cabinet -->
        <rect x="96" y="104" width="328" height="400" rx="30" class="wm-body wm-line-6" />
        <line x1="96" y1="192" x2="424" y2="192" class="wm-line-5" />

        <!-- Control display -->
        <rect x="112" y="126" width="132" height="48" rx="10" class="wm-glass" />
        <text x="178" y="159" text-anchor="middle" class="wm-display">{{ wibTime }}</text>
        <circle cx="408" cy="150" r="5" class="wm-led" />

        <!-- Wordmark on the machine -->
        <text x="262" y="148" class="wm-logo">
            andik<tspan class="wm-logo-dot">.</tspan>
        </text>
        <text x="263" y="163" class="wm-logo-sub">LAUNDRY</text>

        <!-- Program dial -->
        <circle cx="372" cy="150" r="22" class="wm-blue wm-line-4" />
        <line x1="372" y1="150" x2="372" y2="133" class="wm-line-4" stroke-linecap="round" />
        <circle cx="372" cy="150" r="5" class="wm-ink" />

        <!-- Door and drum -->
        <circle cx="260" cy="342" r="126" class="wm-blue wm-line-6" />
        <circle cx="260" cy="342" r="104" class="wm-body wm-line-4-blue" />
        <circle cx="260" cy="342" r="92" class="wm-glass" />

        <g clip-path="url(#wm-drum-clip)">
            <!-- Clothes tumble as the drum turns -->
            <g class="drum-scroll">
                <g transform="translate(260 342)">
                    <path
                        d="M-46 22 L-18 -16 L-6 -8 L0 -28 L26 -28 L32 -8 L44 -16 L72 22 L44 46 L32 32 L32 74 L-6 74 L-6 32 L-18 46 Z"
                        class="wm-teal wm-line-3"
                        stroke-linejoin="round"
                    />
                    <path
                        d="M-62 40 C -40 18, -10 22, -2 44 C 8 70, -22 88, -46 74 C -70 60, -78 48, -62 40 Z"
                        class="wm-yellow wm-line-3"
                    />
                    <path
                        d="M10 -52 C 44 -68, 70 -40, 58 -8 C 48 18, 16 24, 4 4 C -6 -14, -4 -40, 10 -52 Z"
                        class="wm-blue-fabric wm-line-3"
                    />
                    <path
                        d="M-30 -50 C -18 -66, 6 -62, 10 -44 C 14 -26, -2 -14, -18 -20 C -34 -26, -40 -40, -30 -50 Z"
                        class="wm-suds wm-line-3"
                    />
                </g>
            </g>

            <!-- Water fills the lower half, then sloshes with the drum -->
            <g class="drum-water">
                <path
                    d="M160 354 Q 194 340 228 354 T 296 354 T 364 354 L 364 434 L 160 434 Z"
                    class="wm-water"
                />
                <path d="M160 354 Q 194 340 228 354 T 296 354 T 364 354" class="wm-water-line" />
            </g>

            <!-- Suds drift on the moving surface -->
            <g class="drum-suds">
                <circle cx="196" cy="360" r="9" class="wm-suds" />
                <circle cx="224" cy="348" r="12" class="wm-suds" />
                <circle cx="252" cy="368" r="8" class="wm-suds" />
                <circle cx="304" cy="354" r="11" class="wm-suds" />
                <circle cx="332" cy="370" r="7" class="wm-suds" />
            </g>

            <!-- Bubbles rise from the water and fade at the surface -->
            <g class="drum-bubbles">
                <circle cx="228" cy="414" r="2.5" class="wm-bubble" />
                <circle cx="282" cy="420" r="2" class="wm-bubble" />
                <circle cx="316" cy="410" r="2.8" class="wm-bubble" />
            </g>

            <!-- Glass reflection -->
            <path d="M190 292 A 76 76 0 0 1 316 278 A 70 70 0 0 0 200 308 Z" class="wm-glass-shine" />
        </g>

        <circle cx="260" cy="342" r="92" class="wm-line-2-blue" />

        <!-- Door handle -->
        <rect x="392" y="312" width="14" height="60" rx="7" class="wm-ink" />

        <!-- Laundry basket -->
        <ellipse cx="475" cy="516" rx="52" ry="9" class="wm-shadow" />
        <g class="wm-basket">
            <rect x="438" y="466" width="74" height="48" rx="10" class="wm-basket-body wm-line-4" />
            <path d="M446 480 H 504" class="wm-basket-weave" />
            <path d="M446 494 H 504" class="wm-basket-weave" />
            <path d="M458 444 C 458 436, 470 432, 478 438 L 478 458 L 458 458 Z" class="wm-basket-cloth-1 wm-line-3" />
            <path d="M474 440 C 480 430, 496 432, 500 442 L 500 458 L 474 458 Z" class="wm-basket-cloth-2 wm-line-3" />
            <rect x="430" y="458" width="90" height="14" rx="7" class="wm-basket-rim wm-line-4" />
        </g>
    </svg>
</template>

<style scoped>
.wm-aura {
    fill: var(--color-primary-100);
    opacity: 0.55;
    transform-box: view-box;
    transform-origin: 260px 304px;
    animation: aura-breathe 6s ease-in-out infinite;
}

.wm-ring {
    stroke: var(--color-accent-100);
    stroke-width: 2;
    transform-box: view-box;
    transform-origin: 260px 304px;
    animation: ring-orbit 50s linear infinite;
}

.wm-shadow {
    fill: var(--color-navy-950);
    opacity: 0.12;
}

.wm-body {
    fill: var(--color-surface);
}

.wm-blue {
    fill: var(--color-primary-100);
}

.wm-ink {
    fill: var(--color-navy-900);
}

.wm-glass {
    fill: var(--color-navy-950);
}

.wm-teal {
    fill: var(--color-accent-500);
}

.wm-yellow {
    fill: var(--color-warning);
}

.wm-blue-fabric {
    fill: var(--color-primary-500);
}

.wm-suds {
    fill: var(--color-surface);
    opacity: 0.88;
}

.wm-water {
    fill: var(--color-primary-500);
    opacity: 0.65;
}

.wm-water-line {
    stroke: var(--color-primary-100);
    stroke-width: 3;
    opacity: 0.8;
}

.wm-rack-mount {
    fill: var(--color-navy-900);
}

.wm-rack-bar {
    fill: var(--color-warning);
}

.wm-hanger {
    fill: none;
    stroke: var(--color-navy-900);
    stroke-width: 3;
    stroke-linecap: round;
    stroke-linejoin: round;
}

.wm-shirt-teal {
    fill: var(--color-accent-500);
}

.wm-shirt-blue {
    fill: var(--color-primary-500);
}

.wm-shirt-yellow {
    fill: var(--color-warning);
}

.wm-hanging {
    transform-box: fill-box;
    transform-origin: center top;
    animation: hanger-sway 5.4s ease-in-out infinite;
}

.wm-hanging:nth-of-type(2) {
    animation-delay: -1.6s;
    animation-duration: 6.2s;
}

.wm-hanging:nth-of-type(3) {
    animation-delay: -3.1s;
    animation-duration: 4.8s;
}

.wm-detergent-body {
    fill: var(--color-primary-500);
}

.wm-detergent-cap {
    fill: var(--color-warning);
}

.wm-detergent-label {
    fill: var(--color-surface);
}

.wm-detergent-wave {
    stroke: var(--color-accent-600);
    stroke-width: 3;
    stroke-linecap: round;
}

.wm-detergent-text {
    fill: var(--color-navy-950);
    font-family: var(--font-body);
    font-size: 6px;
    font-weight: 700;
    letter-spacing: 0.12em;
}

.wm-basket-body {
    fill: var(--color-accent-100);
}

.wm-basket-rim {
    fill: var(--color-surface);
}

.wm-basket-cloth-1 {
    fill: var(--color-accent-500);
}

.wm-basket-cloth-2 {
    fill: var(--color-primary-500);
}

.wm-basket-weave {
    stroke: var(--color-navy-900);
    stroke-width: 2;
    opacity: 0.18;
}

.wm-led {
    fill: var(--color-accent-500);
    transform-box: fill-box;
    transform-origin: center;
    animation: led-pulse 2.4s ease-in-out infinite;
}

.drum-water {
    animation: water-slosh 4.6s ease-in-out infinite;
    transform-box: view-box;
    transform-origin: 260px 394px;
    will-change: transform;
}

.drum-suds .wm-suds {
    animation: suds-drift 3.8s ease-in-out infinite;
}

.drum-suds .wm-suds:nth-child(2) {
    animation-delay: -0.9s;
    animation-duration: 4.6s;
}

.drum-suds .wm-suds:nth-child(3) {
    animation-delay: -1.8s;
    animation-duration: 3.2s;
}

.drum-suds .wm-suds:nth-child(4) {
    animation-delay: -2.4s;
    animation-duration: 4.1s;
}

.drum-suds .wm-suds:nth-child(5) {
    animation-delay: -0.4s;
    animation-duration: 3.5s;
}

.drum-bubbles .wm-bubble {
    fill: var(--color-surface);
    opacity: 0;
    transform-box: fill-box;
    transform-origin: center;
    animation: bubble-rise 4.8s ease-in infinite;
}

.drum-bubbles .wm-bubble:nth-child(2) {
    animation-delay: -1.6s;
    animation-duration: 5.6s;
}

.drum-bubbles .wm-bubble:nth-child(3) {
    animation-delay: -3.1s;
    animation-duration: 4.2s;
}

@keyframes aura-breathe {
    0%,
    100% {
        transform: scale(1);
        opacity: 0.55;
    }
    50% {
        transform: scale(1.02);
        opacity: 0.4;
    }
}

@keyframes ring-orbit {
    to {
        transform: rotate(360deg);
    }
}

@keyframes led-pulse {
    0%,
    100% {
        opacity: 1;
    }
    50% {
        opacity: 0.25;
    }
}

@keyframes hanger-sway {
    0%,
    100% {
        transform: rotate(-1.1deg);
    }
    50% {
        transform: rotate(1.1deg);
    }
}

@keyframes water-slosh {
    0%,
    100% {
        transform: translate(0, 0) rotate(0deg);
    }
    25% {
        transform: translate(-2px, -1.5px) rotate(-1.5deg);
    }
    50% {
        transform: translate(0, 1.5px) rotate(0deg);
    }
    75% {
        transform: translate(2px, -1.5px) rotate(1.5deg);
    }
}

@keyframes suds-drift {
    0%,
    100% {
        transform: translate(0, 0);
    }
    50% {
        transform: translate(3px, -3px);
    }
}

@keyframes bubble-rise {
    0% {
        transform: translateY(6px) scale(0.7);
        opacity: 0;
    }
    15% {
        opacity: 0.8;
    }
    80% {
        opacity: 0.55;
    }
    100% {
        transform: translateY(-46px) scale(1.15);
        opacity: 0;
    }
}

.wm-glass-shine {
    fill: var(--color-surface);
    opacity: 0.16;
}

.wm-line-2-blue {
    stroke: var(--color-primary-600);
    stroke-width: 2;
    opacity: 0.6;
}

.wm-line-3 {
    stroke: var(--color-navy-950);
    stroke-width: 3;
}

.wm-line-4 {
    stroke: var(--color-navy-900);
    stroke-width: 4;
}

.wm-line-4-blue {
    stroke: var(--color-primary-600);
    stroke-width: 4;
}

.wm-line-5 {
    stroke: var(--color-navy-900);
    stroke-width: 5;
}

.wm-line-6 {
    stroke: var(--color-navy-900);
    stroke-width: 6;
}

.wm-display {
    fill: var(--color-accent-500);
    font-family: var(--font-mono);
    font-size: 22px;
    font-weight: 700;
}

.wm-logo {
    fill: var(--color-navy-900);
    font-family: var(--font-heading);
    font-size: 18px;
    font-weight: 800;
}

.wm-logo-dot {
    fill: var(--color-accent-600);
}

.wm-logo-sub {
    fill: var(--color-accent-600);
    font-family: var(--font-body);
    font-size: 6.5px;
    font-weight: 700;
    letter-spacing: 0.22em;
}
</style>
