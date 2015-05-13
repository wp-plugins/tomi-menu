<?php
/*
Plugin Name: Tomi Automatic Menu
Description: Automatically add AND remove sub pages from menus. Like WordPress can do with top level pages
Version: 0.1
Author: Tomi Novak
*/

/*  Copyright 2015  Tomi Novak (email : dev.tomi33@gmail.com)

    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License, version 2, as 
    published by the Free Software Foundation.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program; if not, write to the Free Software
    Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/


class TomiMenu {

    function __construct() {
        add_action( 'edit_post', array($this, 'update_menus'));
    }


    function update_menus($post_id) {

        $post = get_post($post_id);

        if ($post->post_type != 'page') {
            return; //Only deal with pages
        }

        // Get menus I can add to (do not add sub-menu items if the menu doesn't add top level items)
        $auto_add = get_option('nav_menu_options');
        if (empty($auto_add) || ! is_array($auto_add) || ! isset($auto_add['auto_add'])) {
            return;
        }
        $auto_add = $auto_add['auto_add'];
        if (empty( $auto_add) || ! is_array($auto_add)) {
            return;
        }

        // Iterate through all the menus to find parent of the page
        foreach ($auto_add as $menu_id) {
            $menu_to_insert_into = NULL;
            $menu_items = wp_get_nav_menu_items($menu_id, array('post_status' => 'publish,draft'));
            if (! is_array( $menu_items )) {
                continue;
            }
            foreach ($menu_items as $menu_item) {
                //If page is already in a menu then see if it should be removed or not
                if ($menu_item->object_id == $post->ID) {
                    //Is the menu item its under still the parent?
                    if ($menu_item->object_id != $post->post_parent) {
                        //No, it is not so remove it here
                        wp_delete_post($menu_item->ID);
                    }
                }
                //IF there is a menu item in the menu that is the posts parent, then that is the menu the post should be put into
                if ($menu_item->object_id == $post->post_parent) {
                    $menu_to_insert_into = $menu_item;
                }
            }
            // Put page in the menu
            if ($menu_to_insert_into) {
                wp_update_nav_menu_item( $menu_id, 0, array(
                    'menu-item-object-id' => $post->ID,
                    'menu-item-object' => $post->post_type,
                    'menu-item-parent-id' => $menu_to_insert_into->ID,
                    'menu-item-type' => 'post_type',
                    'menu-item-status' => 'publish'
                ) );
            }
        }
    }

}

$tomi_menu = new TomiMenu();