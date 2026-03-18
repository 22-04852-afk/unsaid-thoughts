<style>
    @import url('https://fonts.googleapis.com/css2?family=Caveat:wght@400;700&family=Indie+Flower&display=swap');

    /* Header */
    header {
        background: linear-gradient(135deg, #FFB6D9 0%, #FF91C5 50%, #FF69B4 100%);
        padding: 2.5rem 1rem;
        text-align: center;
        border-bottom: 2px dashed #FF69B4;
        box-shadow: 0 8px 30px rgba(255, 105, 180, 0.35);
        position: relative;
        overflow: hidden;
    }

    .back-btn {
        position: absolute;
        top: 14px;
        left: 14px;
        z-index: 3;
        border: 1px solid rgba(255, 255, 255, 0.78);
        background: linear-gradient(180deg, rgba(255, 255, 255, 0.96) 0%, rgba(255, 242, 249, 0.96) 100%);
        color: #D72684;
        font-size: 0.8rem;
        font-weight: 800;
        text-decoration: none;
        text-transform: uppercase;
        letter-spacing: 0.8px;
        padding: 0.42rem 0.82rem;
        border-radius: 999px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        min-height: 34px;
        line-height: 1;
        backdrop-filter: blur(4px);
        box-shadow: 0 6px 16px rgba(190, 28, 107, 0.2), inset 0 1px 0 rgba(255, 255, 255, 0.9);
        transition: transform 0.22s ease, background 0.22s ease, color 0.22s ease, box-shadow 0.22s ease;
    }

    .back-btn:hover {
        background: linear-gradient(180deg, rgba(255, 255, 255, 1) 0%, rgba(255, 238, 247, 1) 100%);
        color: #BD176F;
        transform: translateY(-1px);
        box-shadow: 0 10px 24px rgba(190, 28, 107, 0.3), inset 0 1px 0 rgba(255, 255, 255, 0.95);
    }

    .back-btn:active {
        transform: translateY(0) scale(0.98);
    }

    .back-btn:focus-visible {
        outline: 3px solid rgba(255, 255, 255, 0.95);
        outline-offset: 2px;
    }

    header::before {
        content: '★';
        position: absolute;
        top: 10px;
        left: 110px;
        font-size: 1.5rem;
        opacity: 0.4;
        animation: float 4s ease-in-out infinite;
    }

    header::after {
        content: '◈';
        position: absolute;
        top: 10px;
        right: 20px;
        font-size: 1.5rem;
        opacity: 0.4;
        animation: float 4s ease-in-out infinite reverse;
    }

    header h1 {
        font-size: 2.8rem;
        color: white;
        font-weight: 700;
        letter-spacing: 0.5px;
        margin-bottom: 0.3rem;
        text-shadow: 0 4px 10px rgba(0, 0, 0, 0.15);
        position: relative;
        z-index: 1;
        font-family: 'Caveat', cursive;
        font-style: normal;
    }

    header p {
        color: rgba(255, 255, 255, 0.95);
        font-size: 0.9rem;
        font-style: italic;
        font-weight: 500;
        position: relative;
        z-index: 1;
        text-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
    }

    @keyframes float {
        0%, 100% { transform: translateY(0px); }
        50% { transform: translateY(-12px); }
    }

    /* Responsive */
    @media (max-width: 480px) {
        header {
            padding: 2rem 1rem;
        }

        .back-btn {
            top: 10px;
            left: 10px;
            font-size: 0.74rem;
            padding: 0.38rem 0.7rem;
            min-height: 32px;
        }

        header h1 {
            font-size: 1.8rem;
        }

        header::before,
        header::after {
            font-size: 1.2rem;
        }
    }
</style>

<header>
    <a class="back-btn" href="home.php" onclick="if (window.history.length > 1) { event.preventDefault(); window.history.back(); }" aria-label="Go back">Back</a>
    <h1><?php echo isset($header_title) ? $header_title : '⊹ Unsaid Thoughts'; ?></h1>
    <p><?php echo isset($header_subtitle) ? $header_subtitle : 'Everything you never said'; ?></p>
</header>
