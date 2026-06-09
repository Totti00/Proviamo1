<?php
if (!defined('ABSPATH')) {
    exit;
}

$types = get_terms(['taxonomy' => 'property_type', 'hide_empty' => false]);
$locations = get_terms(['taxonomy' => 'property_location', 'hide_empty' => false]);
$paged = max(1, (int) get_query_var('paged', 1));
$query = REB_Real_Estate::get_filtered_properties($paged);
$price_min = REB_Real_Estate::request_text('price_min');
$price_max = REB_Real_Estate::request_text('price_max');
$property_type = REB_Real_Estate::request_text('property_type');
$property_location = REB_Real_Estate::request_text('property_location');
$include_sold = REB_Real_Estate::request_text('include_sold') !== '';
?>
<div class="reb-wrap">
    <form class="reb-filter-form" method="get">
        <div class="reb-fields">
            <label>
                <span><?php esc_html_e('Min Price', 'reb-real-estate'); ?></span>
                <input type="number" name="price_min" value="<?php echo esc_attr($price_min); ?>">
            </label>
            <label>
                <span><?php esc_html_e('Max Price', 'reb-real-estate'); ?></span>
                <input type="number" name="price_max" value="<?php echo esc_attr($price_max); ?>">
            </label>
            <label>
                <span><?php esc_html_e('Property Type', 'reb-real-estate'); ?></span>
                <select name="property_type">
                    <option value=""><?php esc_html_e('Any', 'reb-real-estate'); ?></option>
                    <?php foreach ($types as $type) : ?>
                        <option value="<?php echo esc_attr($type->slug); ?>" <?php selected($property_type, $type->slug); ?>>
                            <?php echo esc_html($type->name); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </label>
            <label>
                <span><?php esc_html_e('Location', 'reb-real-estate'); ?></span>
                <select name="property_location">
                    <option value=""><?php esc_html_e('Any', 'reb-real-estate'); ?></option>
                    <?php foreach ($locations as $location) : ?>
                        <option value="<?php echo esc_attr($location->slug); ?>" <?php selected($property_location, $location->slug); ?>>
                            <?php echo esc_html($location->name); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </label>
            <label class="reb-check">
                <input type="checkbox" name="include_sold" value="1" <?php checked($include_sold); ?>>
                <span><?php esc_html_e('Include sold', 'reb-real-estate'); ?></span>
            </label>
        </div>
        <button type="submit"><?php esc_html_e('Search', 'reb-real-estate'); ?></button>
    </form>

    <div class="reb-grid-listings">
        <?php if ($query->have_posts()) : ?>
            <?php while ($query->have_posts()) : $query->the_post(); ?>
                <?php
                $post_id = get_the_ID();
                $price = (float) get_post_meta($post_id, '_reb_price', true);
                $status = (string) get_post_meta($post_id, '_reb_status', true);
                $address = (string) get_post_meta($post_id, '_reb_address', true);
                ?>
                <article class="reb-card">
                    <a href="<?php the_permalink(); ?>" class="reb-thumb">
                        <?php if (has_post_thumbnail()) : ?>
                            <?php the_post_thumbnail('large', ['loading' => 'lazy']); ?>
                        <?php else : ?>
                            <div class="reb-thumb-placeholder"><?php esc_html_e('No image', 'reb-real-estate'); ?></div>
                        <?php endif; ?>
                    </a>
                    <div class="reb-card-content">
                        <h3><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h3>
                        <p class="reb-price"><?php echo esc_html('$' . number_format_i18n($price, 0)); ?></p>
                        <p><?php echo esc_html($address); ?></p>
                        <p class="reb-status <?php echo esc_attr($status); ?>"><?php echo esc_html(ucfirst($status)); ?></p>
                    </div>
                </article>
            <?php endwhile; ?>
            <?php wp_reset_postdata(); ?>
        <?php else : ?>
            <p><?php esc_html_e('No properties found for these filters.', 'reb-real-estate'); ?></p>
        <?php endif; ?>
    </div>

    <?php
    echo wp_kses_post(paginate_links([
        'total' => $query->max_num_pages,
        'current' => $paged,
    ]));
    ?>
</div>
