<div class="wrap">
    <h2>Elasticsearch Indexer Settings</h2>

    <form method="post" action="options.php">
        <?php settings_fields('esi_options_group'); ?>
        <?php do_settings_sections('esi_options_group'); ?>
        <table class="form-table">
            <tr valign="top">
                <th scope="row">Host</th>
                <td>
                    <input type="text" name="esi_hosts"
                           value="<?php echo esc_attr(get_option('esi_hosts', '127.0.0.1:9200')); ?>"/>

                    <p class="description">Multiple separated by commas</p>
                </td>
            </tr>

            <tr valign="top">
                <th scope="row">Shards</th>
                <td>
                    <input type="text" name="esi_shards" value="<?php echo esc_attr(get_option('esi_shards', 5)); ?>"/>

                    <p class="description">Recommended: 5</p>
                </td>
            </tr>

            <tr valign="top">
                <th scope="row">Replicas</th>
                <td>
                    <input type="text" name="esi_replicas"
                           value="<?php echo esc_attr(get_option('esi_replicas', 1)); ?>"/>

                    <p class="description">Recommended: 1</p>
                </td>
            </tr>
            <tr valign="top">
                <th scope="row">Include child taxonomies</th>
                <td>
                    <input type="text" name="esi_filter_subtaxes"
                           value="1" <?php echo get_option('esi_filter_subtaxes', 1) ? 'checked="checked"' : ''; ?>/>

                    <p class="description">Include posts from child taxonomies</p>
                </td>
            </tr>
        </table>

        <?php submit_button(); ?>

    </form>
</div>
