<?php
/**
 * Template Name: Unsubscribe Confirmation
 *
 * This template displays the unsubscribe confirmation page.
 *
 * @package ccs-theme
 */

get_header();

$is_unsubscribe_request = isset($_GET['ccs_unsubscribe']) && isset($_GET['email']) && isset($_GET['token']);
$unsubscribed = isset($_GET['unsubscribed']) && $_GET['unsubscribed'] === '1';
$email = isset($_GET['email']) ? sanitize_email($_GET['email']) : '';

if ($is_unsubscribe_request && !$unsubscribed) {
    ?>
    <main id="main-content">
        <section class="page-hero-section page-hero-section-simple">
            <div class="container">
                <h1 class="hero-title">Processing unsubscribe request...</h1>
            </div>
        </section>
        <section class="legal-content-section">
            <div class="container">
                <div class="legal-content" style="max-width: 600px; margin: 0 auto; text-align: center; padding: 40px 20px;">
                    <p>Please wait while we process your unsubscribe request...</p>
                </div>
            </div>
        </section>
    </main>
    <?php
    get_footer();
    exit;
}
?>

<main id="main-content">
    <section class="page-hero-section page-hero-section-simple" aria-labelledby="unsubscribe-heading">
        <div class="container">
            <nav aria-label="Breadcrumb" class="breadcrumb breadcrumb-hero">
                <ol class="breadcrumb-list">
                    <li class="breadcrumb-item">
                        <a href="<?php echo esc_url(home_url('/')); ?>" class="breadcrumb-link">Home</a>
                    </li>
                    <li class="breadcrumb-separator" aria-hidden="true">/</li>
                    <li class="breadcrumb-item">
                        <span class="breadcrumb-current" aria-current="page">Unsubscribe</span>
                    </li>
                </ol>
            </nav>
            <h1 id="unsubscribe-heading" class="hero-title">Newsletter Unsubscribe</h1>
        </div>
    </section>

    <section class="legal-content-section">
        <div class="container">
            <div class="legal-content" style="max-width: 600px; margin: 0 auto;">
                <?php if ($unsubscribed && !empty($email)) : ?>
                    <div style="text-align: center; padding: 40px 20px;">
                        <div style="font-size: 64px; margin-bottom: 20px;">✓</div>
                        <h2 style="margin-bottom: 16px; color: #00a32a;">You've been unsubscribed</h2>
                        <p style="font-size: 16px; line-height: 1.6; color: #2b1b0e; margin-bottom: 30px;">
                            You will no longer receive newsletter emails from Continuity of Care Services.
                        </p>
                        <?php if (!empty($email)) : ?>
                            <p style="font-size: 14px; color: #646970; margin-bottom: 30px;">
                                Email: <strong><?php echo esc_html($email); ?></strong>
                            </p>
                        <?php endif; ?>
                        <div style="margin-top: 40px;">
                            <a href="<?php echo esc_url(home_url('/')); ?>" class="btn btn-primary" style="display: inline-block;">
                                Return to Homepage
                            </a>
                        </div>
                    </div>
                <?php else : ?>
                    <div style="text-align: center; padding: 40px 20px;">
                        <h2 style="margin-bottom: 16px;">Unsubscribe from Newsletter</h2>
                        <p style="font-size: 16px; line-height: 1.6; color: #2b1b0e; margin-bottom: 30px;">
                            If you received an unsubscribe link in an email, please use that link to unsubscribe. 
                            If you need assistance, please <a href="<?php echo esc_url(ccs_page_url('contact')); ?>">contact us</a>.
                        </p>
                        <div style="margin-top: 40px;">
                            <a href="<?php echo esc_url(home_url('/')); ?>" class="btn btn-primary" style="display: inline-block;">
                                Return to Homepage
                            </a>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </section>
</main>

<?php get_footer(); ?>

