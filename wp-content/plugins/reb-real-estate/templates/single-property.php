<?php
if (!defined('ABSPATH')) {
    exit;
}

get_header();

if (have_posts()) :
    while (have_posts()) :
        the_post();
        $post_id = get_the_ID();
        $price = (float) get_post_meta($post_id, '_reb_price', true);
        $status = (string) get_post_meta($post_id, '_reb_status', true);
        $address = (string) get_post_meta($post_id, '_reb_address', true);
        $lat = (string) get_post_meta($post_id, '_reb_latitude', true);
        $lng = (string) get_post_meta($post_id, '_reb_longitude', true);
        $features = array_filter(array_map('trim', explode(',', (string) get_post_meta($post_id, '_reb_features', true))));
        $gallery_ids = REB_Real_Estate::get_gallery_ids($post_id);
        $is_client = is_user_logged_in() && in_array('client', (array) wp_get_current_user()->roles, true);
        $is_favorite = $is_client ? REB_Real_Estate::is_favorite($post_id, get_current_user_id()) : false;
        ?>
        <main class="reb-single">
            <h1><?php the_title(); ?></h1>
            <p class="reb-price"><?php echo esc_html('$' . number_format_i18n($price, 0)); ?></p>
            <p class="reb-status <?php echo esc_attr($status); ?>"><?php echo esc_html(ucfirst($status)); ?></p>
            <p><?php echo esc_html($address); ?></p>

            <div class="reb-gallery">
                <?php if (has_post_thumbnail()) : ?>
                    <div class="reb-gallery-item"><?php the_post_thumbnail('large', ['loading' => 'lazy']); ?></div>
                <?php endif; ?>
                <?php foreach ($gallery_ids as $img_id) : ?>
                    <div class="reb-gallery-item"><?php echo wp_get_attachment_image($img_id, 'large', false, ['loading' => 'lazy']); ?></div>
                <?php endforeach; ?>
            </div>

            <?php if ($lat !== '' && $lng !== '') : ?>
                <div id="reb-map" class="reb-map" data-lat="<?php echo esc_attr($lat); ?>" data-lng="<?php echo esc_attr($lng); ?>"></div>
            <?php endif; ?>

            <?php if ($features) : ?>
                <h2><?php esc_html_e('Features', 'reb-real-estate'); ?></h2>
                <ul class="reb-features">
                    <?php foreach ($features as $feature) : ?>
                        <li><?php echo esc_html($feature); ?></li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>

            <div class="reb-content"><?php the_content(); ?></div>

            <?php if ($is_client) : ?>
                <button class="reb-favorite-btn" data-post-id="<?php echo esc_attr((string) $post_id); ?>" aria-pressed="<?php echo $is_favorite ? 'true' : 'false'; ?>">
                    <?php echo esc_html($is_favorite ? __('Remove from favorites', 'reb-real-estate') : __('Save to favorites', 'reb-real-estate')); ?>
                </button>
            <?php endif; ?>

            <?php $rebContactStatus = REB_Real_Estate::request_text('reb_contact'); ?>
            <?php if ($rebContactStatus === 'sent') : ?>
                <p class="reb-notice success"><?php esc_html_e('Message sent successfully.', 'reb-real-estate'); ?></p>
            <?php elseif ($rebContactStatus === 'failed') : ?>
                <p class="reb-notice error"><?php esc_html_e('Message could not be sent. Please try again.', 'reb-real-estate'); ?></p>
            <?php endif; ?>

            <section class="reb-contact">
                <h2><?php esc_html_e('Contact Agent', 'reb-real-estate'); ?></h2>
                <form action="<?php echo esc_url(admin_url('admin-post.php')); ?>" method="post">
                    <?php wp_nonce_field('reb_contact', 'reb_contact_nonce'); ?>
                    <input type="hidden" name="action" value="reb_contact_agent">
                    <input type="hidden" name="property_id" value="<?php echo esc_attr((string) $post_id); ?>">
                    <label><?php esc_html_e('Name', 'reb-real-estate'); ?><input required name="name" type="text"></label>
                    <label><?php esc_html_e('Email', 'reb-real-estate'); ?><input required name="email" type="email"></label>
                    <label><?php esc_html_e('Message', 'reb-real-estate'); ?><textarea required name="message" rows="4"></textarea></label>
                    <button type="submit"><?php esc_html_e('Send Inquiry', 'reb-real-estate'); ?></button>
                </form>
            </section>
        </main>
        <?php
    endwhile;
endif;

get_footer();
