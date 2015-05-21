<?php

use Wallmander\ElasticsearchIndexer\Model\Config;

?>
<div class="wrap esi-settings">
    <h2>Elasticsearch Indexer Settings</h2>

    <p>Remember to reindex posts after changing options</p>

    <form method="post" action="options.php">
        <?php settings_fields('esi_options_group'); ?>
        <?php do_settings_sections('esi_options_group'); ?>
        <table class="form-table">

            <tr valign="top">
                <th>Enable Integration</th>
                <td>
                    <input type="hidden" name="<?php echo Config::optionKey('enable_integration') ?>"
                           value="0"/>
                    <input type="checkbox" name="<?php echo Config::optionKey('enable_integration') ?>"
                           value="1" <?php echo Config::option('enable_integration') ? 'checked="checked"' : ''; ?>/>

                    <p class="description">Posts will still be synced if disabled.</p>
                </td>
            </tr>

            <tr valign="top">
                <th>Host(s)</th>
                <td>
                    <textarea name="<?php echo Config::optionKey('hosts') ?>"><?php echo Config::option('hosts'); ?></textarea>

                    <p class="description">
                        Example: <code>127.0.0.1:9000</code>, <code>http://127.0.0.1:9000</code> or <code>https://127.0.0.1:9000</code><br>
                        Split multiple hosts between lines.<br>
                        Note! Default port is 80. If using https, you will have to specify the port. Example <code>https://user:pass@xxxx.bonsai.io:443</code><br>
                    </p>
                    <table class="esi-connection">
                        <?php foreach ($hostsStatus as $i => $status) : ?>
                            <tr>
                                <td>
                                    Host Status<?php echo count($hostsStatus) > 1 ? ' ('.($i + 1).')' : '' ?>
                                </td>
                                <td>
                                    <?php if ($status['time'] < 50) : ?>
                                        <span class="esi-connection-ok">
                                            <?php echo number_format($status['time'], 3, '.', '&nbsp;') ?>&nbsp;ms
                                        </span>
                                    <?php elseif ($status['time'] < 150) : ?>
                                        <span class="esi-connection-warning">
                                            <?php echo number_format($status['time'], 3, '.', '&nbsp;') ?>&nbsp;ms
                                        </span>
                                    <?php else : ?>
                                        <span class="esi-connection-error">
                                            <?php echo number_format($status['time'], 3, '.', '&nbsp;') ?>&nbsp;ms
                                        </span>
                                    <?php endif ?>
                                </td>
                                <td>
                                    <?php if ($status['success']) : ?>
                                        <span class="esi-connection-ok">
                                            <?php echo $status['status'] ?>
                                        </span>
                                    <?php else : ?>
                                        <span class="esi-connection-error">
                                            <?php echo $status['status'] ?>
                                        </span>
                                    <?php endif ?>
                                </td>
                            </tr>
                        <?php endforeach ?>
                    </table>

                </td>
            </tr>

            <tr valign="top">
                <th>Shards</th>
                <td>
                    <input type="number" name="<?php echo Config::optionKey('shards') ?>" min="1"
                           value="<?php echo Config::option('shards'); ?>"/>

                    <p class="description">Recommended: 5</p>
                </td>
            </tr>

            <tr valign="top">
                <th>Replicas</th>
                <td>
                    <input type="number" name="<?php echo Config::optionKey('replicas') ?>" min="0"
                           value="<?php echo Config::option('replicas'); ?>"/>

                    <p class="description">Recommended: 1</p>
                </td>
            </tr>
            <tr valign="top">
                <th>Index private post types</th>
                <td>
                    <input type="hidden" name="<?php echo Config::optionKey('index_private_post_types') ?>" value="0"/>
                    <input type="checkbox" name="<?php echo Config::optionKey('index_private_post_types') ?>"
                           value="1" <?php echo Config::option('index_private_post_types') ? 'checked="checked"' : ''; ?>/>

                    <p class="description">
                        Allow Elasticsearch to index non public post types. This could speed up admin some admin
                        pages.<br>
                        It is important that you reindex all posts after changing this option.
                    </p>
                </td>
            </tr>
            <tr valign="top">
                <th>Include child taxonomies</th>
                <td>
                    <input type="hidden" name="<?php echo Config::optionKey('include_posts_from_child_taxonomies') ?>"
                           value="0"/>
                    <input type="checkbox" name="<?php echo Config::optionKey('include_posts_from_child_taxonomies') ?>"
                           value="1" <?php echo Config::option('include_posts_from_child_taxonomies') ? 'checked="checked"' : ''; ?>/>

                    <p class="description">Include posts from child taxonomies</p>
                </td>
            </tr>
            <tr valign="top">
                <th>Enable Profiler Frontend</th>
                <td>
                    <input type="hidden" name="<?php echo Config::optionKey('profile_frontend') ?>"
                           value="0"/>
                    <input type="checkbox" name="<?php echo Config::optionKey('profile_frontend') ?>"
                           value="1" <?php echo Config::option('profile_frontend') ? 'checked="checked"' : ''; ?>/>

                    <p class="description"></p>
                </td>
            </tr>
            <tr valign="top">
                <th>Enable Profiler Admin</th>
                <td>
                    <input type="hidden" name="<?php echo Config::optionKey('profile_admin') ?>"
                           value="0"/>
                    <input type="checkbox" name="<?php echo Config::optionKey('profile_admin') ?>"
                           value="1" <?php echo Config::option('profile_admin') ? 'checked="checked"' : ''; ?>/>

                    <p class="description"></p>
                </td>
            </tr>
        </table>

        <?php submit_button(); ?>

    </form>
</div>
