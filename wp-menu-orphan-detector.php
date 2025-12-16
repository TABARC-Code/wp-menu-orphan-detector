<?php
/**
 * Plugin Name: WP Menu Orphan Detector
 * Plugin URI: https://github.com/TABARC-Code/wp-menu-orphan-detector
 * Description: Scans nav menus for items that point at missing posts, terms or broken internal URLs and lists them so I know what is quietly rotting.
 * Version: 1.0.0.6
 * Author: TABARC-Code
 * Author URI: https://github.com/TABARC-Code
 * License: GPL-3.0-or-later
 * License URI: https://www.gnu.org/licenses/gpl-3.0.html
 *
 * Copyright (c) 2025 TABARC-Code
 * Original work by TABARC-Code.
 * You may modify and redistribute this software under the terms of
 * the GNU General Public License version 3 or (at your option) any later version.
 * Keep this notice and be honest about your changes.
 *
 * Why this exists:
 * Every long lived WordPress site ends up with nav menu items that point at content
 * which no longer exists. Old landing pages, removed categories, test content that
 * someone deleted. The menu happily keeps the links and nobody notices until a user
 * clicks one and hits a dead end.
 *
 * This plugin gives me a read only screen that:
 * - Scans all nav menus.
 * - Flags items pointing at missing posts or terms.
 * - Flags parent items that no longer exist.
 * - Flags internal custom URLs that do not resolve to any content.
 *
 * It does not fix anything. It just points at the mess in the navigation and smirks.
 *
 * TODO: add a way to export the orphan report as CSV.
 * TODO: add filters to limit checks to certain menus or locations.
 * FIXME: url_to_postid is not perfect, so the "maybe broken" list is intentionally conservative.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

if ( ! class_exists( 'WP_Menu_Orphan_Detector' ) ) {

    class WP_Menu_Orphan_Detector {

        private $screen_slug = 'wp-menu-orphan-detector';

        public function __construct() {
            add_action( 'admin_menu', array( $this, 'add_appearance_page' ) );
            add_action( 'admin_head-plugins.php', array( $this, 'inject_plugin_list_icon_css' ) );
        }

        /**
         * Shared icon location to keep branding consistent across projects.
         */
        private function get_brand_icon_url() {
            return plugin_dir_url( __FILE__ ) . '.branding/tabarc-icon.svg';
        }

        /**
         * Attach under Appearance so I remember it exists when fiddling with menus.
         */
        public function add_appearance_page() {
            add_theme_page(
                __( 'Menu Orphan Detector', 'wp-menu-orphan-detector' ),
                __( 'Menu Orphans', 'wp-menu-orphan-detector' ),
                'edit_theme_options',
                $this->screen_slug,
                array( $this, 'render_screen' )
            );
        }

        public function render_screen() {
            if ( ! current_user_can( 'edit_theme_options' ) ) {
                wp_die( esc_html__( 'You do not have permission to access this page.', 'wp-menu-orphan-detector' ) );
            }

            $menus = wp_get_nav_menus();

            if ( empty( $menus ) ) {
                echo '<div class="wrap">';
                echo '<h1>' . esc_html__( 'Menu Orphan Detector', 'wp-menu-orphan-detector' ) . '</h1>';
                echo '<p>' . esc_html__( 'There are no nav menus defined on this site. Which is one way to avoid broken links, I suppose.', 'wp-menu-orphan-detector' ) . '</p>';
                echo '</div>';
                return;
            }

            $report = $this->build_report( $menus );

            ?>
            <div class="wrap">
                <h1><?php esc_html_e( 'Menu Orphan Detector', 'wp-menu-orphan-detector' ); ?></h1>
                <p>
                    This screen inspects all nav menus and tries to answer a simple question:
                    how many of these links still point at something that exists.
                </p>

                <h2><?php esc_html_e( 'Summary', 'wp-menu-orphan-detector' ); ?></h2>
                <?php $this->render_summary( $report ); ?>

                <h2><?php esc_html_e( 'Items pointing at missing content', 'wp-menu-orphan-detector' ); ?></h2>
                <p>
                    These menu items claim to point at posts or terms that no longer exist,
                    or have been moved out of public view. Users clicking these see nothing helpful.
                </p>
                <?php $this->render_missing_items_table( $report ); ?>

                <h2><?php esc_html_e( 'Items with missing parents', 'wp-menu-orphan-detector' ); ?></h2>
                <p>
                    These are child items whose parent menu item is missing or points at missing content.
                    They may still render, but the hierarchy is broken and confusing.
                </p>
                <?php $this->render_orphan_children_table( $report ); ?>

                <h2><?php esc_html_e( 'Internal custom URLs that look suspicious', 'wp-menu-orphan-detector' ); ?></h2>
                <p>
                    These custom link menu items use an internal URL that does not appear to match any post,
                    page or term on the site. This is a best effort guess, not a guarantee.
                </p>
                <?php $this->render_suspicious_custom_table( $report ); ?>
            </div>
            <?php
        }

        /**
         * Build the full report for all menus.
         */
        private function build_report( $menus ) {
            $report = array(
                'menus'                => array(),
                'missing_items'        => array(),
                'orphan_children'      => array(),
                'suspicious_custom'    => array(),
                'total_items'          => 0,
                'total_menus'          => count( $menus ),
            );

            foreach ( $menus as $menu ) {
                $items = wp_get_nav_menu_items( $menu->term_id, array( 'update_post_term_cache' => false ) );

                if ( empty( $items ) ) {
                    continue;
                }

                $indexed = array();
                foreach ( $items as $item ) {
                    $indexed[ $item->ID ] = $item;
                }

                $menu_entry = array(
                    'menu'   => $menu,
                    'items'  => $items,
                    'stats'  => array(
                        'total'       => count( $items ),
                        'missing'     => 0,
                        'suspicious'  => 0,
                        'orphans'     => 0,
                    ),
                );

                foreach ( $items as $item ) {
                    $report['total_items']++;

                    $missing_reason = $this->get_missing_reason_for_item( $item );
                    $is_missing = ! empty( $missing_reason );

                    if ( $is_missing ) {
                        $menu_entry['stats']['missing']++;
                        $report['missing_items'][] = array(
                            'menu'   => $menu,
                            'item'   => $item,
                            'reason' => $missing_reason,
                        );
                    }

                    $is_suspicious = false;
                    if ( $item->type === 'custom' ) {
                        $suspicious_reason = $this->get_suspicious_reason_for_custom_url( $item->url );
                        if ( $suspicious_reason ) {
                            $is_suspicious = true;
                            $menu_entry['stats']['suspicious']++;
                            $report['suspicious_custom'][] = array(
                                'menu'   => $menu,
                                'item'   => $item,
                                'reason' => $suspicious_reason,
                            );
                        }
                    }

                    $parent_orphan = false;
                    if ( $item->menu_item_parent ) {
                        $parent_id = (int) $item->menu_item_parent;
                        if ( ! isset( $indexed[ $parent_id ] ) ) {
                            $parent_orphan = true;
                        } else {
                            $parent     = $indexed[ $parent_id ];
                            $parent_missing_reason = $this->get_missing_reason_for_item( $parent );
                            if ( $parent_missing_reason ) {
                                $parent_orphan = true;
                            }
                        }
                    }

                    if ( $parent_orphan ) {
                        $menu_entry['stats']['orphans']++;
                        $report['orphan_children'][] = array(
                            'menu'   => $menu,
                            'item'   => $item,
                        );
                    }
                }

                $report['menus'][] = $menu_entry;
            }

            return $report;
        }

        /**
         * For a standard menu item type, work out if the target resource is missing.
         */
        private function get_missing_reason_for_item( $item ) {
            switch ( $item->type ) {
                case 'post_type':
                    $post_id = (int) $item->object_id;
                    if ( ! $post_id ) {
                        return __( 'No linked post id recorded.', 'wp-menu-orphan-detector' );
                    }
                    $post = get_post( $post_id );
                    if ( ! $post ) {
                        return __( 'Linked post no longer exists.', 'wp-menu-orphan-detector' );
                    }
                    if ( $post->post_status !== 'publish' && $post->post_status !== 'private' ) {
                        return sprintf(
                            __( 'Linked post exists but has status %s.', 'wp-menu-orphan-detector' ),
                            $post->post_status
                        );
                    }
                    return '';

                case 'taxonomy':
                    $term_id  = (int) $item->object_id;
                    $taxonomy = $item->object;
                    if ( ! $term_id || ! $taxonomy ) {
                        return __( 'No linked term or taxonomy recorded.', 'wp-menu-orphan-detector' );
                    }
                    $term = get_term( $term_id, $taxonomy );
                    if ( ! $term || is_wp_error( $term ) ) {
                        return __( 'Linked term no longer exists.', 'wp-menu-orphan-detector' );
                    }
                    return '';

                case 'custom':
                    // Custom links are handled separately. Here we say nothing.
                    return '';

                default:
                    // Other exotic types are not deeply inspected in this version.
                    return '';
            }
        }

        /**
         * Try to guess if a custom URL that claims to be internal looks broken.
         *
         * I treat external URLs as off limits. For internal ones I try url_to_postid,
         * which is not perfect but gives a decent signal.
         */
        private function get_suspicious_reason_for_custom_url( $url ) {
            $url = trim( (string) $url );
            if ( $url === '' ) {
                return __( 'Empty URL for custom menu item.', 'wp-menu-orphan-detector' );
            }

            $home = home_url();
            if ( stripos( $url, $home ) !== 0 ) {
                return '';
            }

            $relative = substr( $url, strlen( $home ) );
            $relative = ltrim( $relative, '/' );

            if ( $relative === '' ) {
                return '';
            }

            $post_id = url_to_postid( $url );

            if ( $post_id ) {
                $post = get_post( $post_id );
                if ( $post && ( $post->post_status === 'publish' || $post->post_status === 'private' ) ) {
                    return '';
                }
                return __( 'Custom URL resolves to a post that is not publicly available.', 'wp-menu-orphan-detector' );
            }

            return __( 'Custom URL does not appear to resolve to any known post or page.', 'wp-menu-orphan-detector' );
        }

        private function render_summary( $report ) {
            $total_menus  = (int) $report['total_menus'];
            $total_items  = (int) $report['total_items'];
            $missing      = count( $report['missing_items'] );
            $orphans      = count( $report['orphan_children'] );
            $suspicious   = count( $report['suspicious_custom'] );

            ?>
            <table class="widefat striped" style="max-width:800px;">
                <tbody>
                    <tr>
                        <th><?php esc_html_e( 'Menus scanned', 'wp-menu-orphan-detector' ); ?></th>
                        <td><?php echo esc_html( $total_menus ); ?></td>
                    </tr>
                    <tr>
                        <th><?php esc_html_e( 'Total menu items', 'wp-menu-orphan-detector' ); ?></th>
                        <td><?php echo esc_html( $total_items ); ?></td>
                    </tr>
                    <tr>
                        <th><?php esc_html_e( 'Items pointing at missing content', 'wp-menu-orphan-detector' ); ?></th>
                        <td><?php echo esc_html( $missing ); ?></td>
                    </tr>
                    <tr>
                        <th><?php esc_html_e( 'Children with missing or broken parents', 'wp-menu-orphan-detector' ); ?></th>
                        <td><?php echo esc_html( $orphans ); ?></td>
                    </tr>
                    <tr>
                        <th><?php esc_html_e( 'Suspicious internal custom URLs', 'wp-menu-orphan-detector' ); ?></th>
                        <td><?php echo esc_html( $suspicious ); ?></td>
                    </tr>
                </tbody>
            </table>
            <?php
        }

        private function render_missing_items_table( $report ) {
            if ( empty( $report['missing_items'] ) ) {
                echo '<p>' . esc_html__( 'No menu items were found pointing at obviously missing posts or terms. Either things are surprisingly tidy or the problem lives elsewhere.', 'wp-menu-orphan-detector' ) . '</p>';
                return;
            }

            ?>
            <table class="widefat striped">
                <thead>
                    <tr>
                        <th><?php esc_html_e( 'Menu', 'wp-menu-orphan-detector' ); ?></th>
                        <th><?php esc_html_e( 'Item label', 'wp-menu-orphan-detector' ); ?></th>
                        <th><?php esc_html_e( 'Type', 'wp-menu-orphan-detector' ); ?></th>
                        <th><?php esc_html_e( 'Reason', 'wp-menu-orphan-detector' ); ?></th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ( $report['missing_items'] as $row ) : ?>
                    <?php
                    $menu = $row['menu'];
                    $item = $row['item'];
                    ?>
                    <tr>
                        <td>
                            <?php echo esc_html( $menu->name ); ?>
                            <br><span style="font-size:12px;opacity:0.7;"><?php echo esc_html( $menu->slug ); ?></span>
                        </td>
                        <td>
                            <?php echo esc_html( $item->title ); ?>
                            <br>
                            <span style="font-size:12px;opacity:0.7;">
                                <?php esc_html_e( 'Menu item ID:', 'wp-menu-orphan-detector' ); ?>
                                <?php echo ' ' . (int) $item->ID; ?>
                            </span>
                        </td>
                        <td>
                            <code><?php echo esc_html( $item->type ); ?></code>
                        </td>
                        <td>
                            <?php echo esc_html( $row['reason'] ); ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
            <p style="font-size:12px;opacity:0.8;">
                <?php esc_html_e( 'Check these in Appearance, Menus before deleting. Some may be intentionally left as draft only or private links.', 'wp-menu-orphan-detector' ); ?>
            </p>
            <?php
        }

        private function render_orphan_children_table( $report ) {
            if ( empty( $report['orphan_children'] ) ) {
                echo '<p>' . esc_html__( 'No menu items were found whose parent is missing or broken. At least navigation hierarchy is not lying to you today.', 'wp-menu-orphan-detector' ) . '</p>';
                return;
            }

            ?>
            <table class="widefat striped">
                <thead>
                    <tr>
                        <th><?php esc_html_e( 'Menu', 'wp-menu-orphan-detector' ); ?></th>
                        <th><?php esc_html_e( 'Child label', 'wp-menu-orphan-detector' ); ?></th>
                        <th><?php esc_html_e( 'Parent id', 'wp-menu-orphan-detector' ); ?></th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ( $report['orphan_children'] as $row ) : ?>
                    <?php
                    $menu = $row['menu'];
                    $item = $row['item'];
                    ?>
                    <tr>
                        <td>
                            <?php echo esc_html( $menu->name ); ?>
                            <br><span style="font-size:12px;opacity:0.7;"><?php echo esc_html( $menu->slug ); ?></span>
                        </td>
                        <td>
                            <?php echo esc_html( $item->title ); ?>
                            <br>
                            <span style="font-size:12px;opacity:0.7;">
                                <?php esc_html_e( 'Menu item ID:', 'wp-menu-orphan-detector' ); ?>
                                <?php echo ' ' . (int) $item->ID; ?>
                            </span>
                        </td>
                        <td>
                            <code><?php echo (int) $item->menu_item_parent; ?></code>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
            <p style="font-size:12px;opacity:0.8;">
                <?php esc_html_e( 'These items may still render, but the structure is broken. You might want to reattach them or flatten them.', 'wp-menu-orphan-detector' ); ?>
            </p>
            <?php
        }

        private function render_suspicious_custom_table( $report ) {
            if ( empty( $report['suspicious_custom'] ) ) {
                echo '<p>' . esc_html__( 'No suspicious internal custom URLs were detected. Either your menus are clean or all the broken links are external.', 'wp-menu-orphan-detector' ) . '</p>';
                return;
            }

            ?>
            <table class="widefat striped">
                <thead>
                    <tr>
                        <th><?php esc_html_e( 'Menu', 'wp-menu-orphan-detector' ); ?></th>
                        <th><?php esc_html_e( 'Item label', 'wp-menu-orphan-detector' ); ?></th>
                        <th><?php esc_html_e( 'URL', 'wp-menu-orphan-detector' ); ?></th>
                        <th><?php esc_html_e( 'Reason', 'wp-menu-orphan-detector' ); ?></th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ( $report['suspicious_custom'] as $row ) : ?>
                    <?php
                    $menu = $row['menu'];
                    $item = $row['item'];
                    ?>
                    <tr>
                        <td>
                            <?php echo esc_html( $menu->name ); ?>
                            <br><span style="font-size:12px;opacity:0.7;"><?php echo esc_html( $menu->slug ); ?></span>
                        </td>
                        <td>
                            <?php echo esc_html( $item->title ); ?>
                            <br>
                            <span style="font-size:12px;opacity:0.7;">
                                <?php esc_html_e( 'Menu item ID:', 'wp-menu-orphan-detector' ); ?>
                                <?php echo ' ' . (int) $item->ID; ?>
                            </span>
                        </td>
                        <td><code><?php echo esc_html( $item->url ); ?></code></td>
                        <td><?php echo esc_html( $row['reason'] ); ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
            <p style="font-size:12px;opacity:0.8;">
                <?php esc_html_e( 'Internal URLs can be tricky. Double check in a browser before you remove these.', 'wp-menu-orphan-detector' ); ?>
            </p>
            <?php
        }

        public function inject_plugin_list_icon_css() {
            $icon_url = esc_url( $this->get_brand_icon_url() );
            ?>
            <style>
                .wp-list-table.plugins tr[data-slug="wp-menu-orphan-detector"] .plugin-title strong::before {
                    content: '';
                    display: inline-block;
                    vertical-align: middle;
                    width: 18px;
                    height: 18px;
                    margin-right: 6px;
                    background-image: url('<?php echo $icon_url; ?>');
                    background-repeat: no-repeat;
                    background-size: contain;
                }
            </style>
            <?php
        }
    }

    new WP_Menu_Orphan_Detector();
}
