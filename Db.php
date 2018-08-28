<?php
namespace viravi;

if (!defined('ABSPATH')) exit;

class Db
{
    function __construct()
    {
    }

    /**
     * get all fb application
     */
    static function get_all_fb_app()
    {
        return get_option('abv_va_fb_settings');
    }

    /**
     * delete fp
     */
    static function del_fp_manage($data)
    {
        $fps = get_option('abv_va_fp_groups', []);
        $arr = [];
        foreach ($fps as $fp) {
            $find = 0;
            foreach ($data as $item) {
                if ($fp['fp_group'] == $item) {
                    $find = 1;
                }
            }
            if ($find) $arr[] = $fp;
        }
        if (!Core::is_user_role('iv_test'))
            update_option('abv_va_fp_groups', $arr);
    }

    /**
     * save cta
     */
    static function save_cta($data)
    {
        $arr = [];
        for ($k = 0; $k <= count($data) - 1; $k = $k + 2) {
            $arr[] = ['title' => $data[$k], 'url' => $data[$k + 1]];
        }
        if (!Core::is_user_role('iv_test'))
            update_option('abv_va_cta', $arr);
    }

    /**
     * save post to viravi table
     */
    static function save_post($data)
    {
        global $wpdb;
        $table_name = $wpdb->get_blog_prefix() . 'abv_viravi';

        if ($data['pub'] == 'now') {
            $posted = 1;
        } else {
            $posted = 0;
        }

        $yt_pic = (new Youtube())->get_info($data['yt_id']);

        $wpdb->insert(
            $table_name,
            array(
                'pic' => $yt_pic->items[0]->snippet->thumbnails->medium->url,
                'url' => 'https://youtu.be/' . $data['yt_id'],
                'fanpage' => $data['fp'],
                'date' => $data['date'],
                'posted' => $posted,
                'fb_id' => $data['fp_post_id'],
                'post_id' => $data['post_id'],
                'fb_desc' => $data['fb_desc'],
            ),
            array(
                '%s',
                '%s',
                '%d',
                '%d',
                '%d',
                '%s',
                '%d',
                '%s',
            )
        );
        return $wpdb->insert_id;
    }

    /**
     * get all post from va table
     */
    static function get_all_posts()
    {
        global $wpdb;
        $table_name = $wpdb->get_blog_prefix() . 'abv_viravi';

        $res = $wpdb->get_results("SELECT * FROM $table_name ORDER BY id DESC ", ARRAY_A);
        return $res;
    }

    /**
     * get single va post
     */
    static function get_va_post($id)
    {
        global $wpdb;
        $table_name = $wpdb->get_blog_prefix() . 'abv_viravi';

        $res = $wpdb->get_results($wpdb->prepare("SELECT * FROM $table_name WHERE id = '%d'", $id), ARRAY_A);
        if ($res) {
            return $res[0];
        } else {
            return false;
        }
    }

    /**
     * update post data
     */
    static function update_post_data($post_id, $unix_time)
    {
        global $wpdb;
        $table_name = $wpdb->get_blog_prefix() . 'posts';
        $wpdb->update($table_name,
            array(
                'post_date' => date('Y-m-d H:i:s', $unix_time),
                'post_modified' => date('Y-m-d H:i:s', $unix_time),
                'post_date_gmt' => gmdate('Y-m-d H:i:s', $unix_time),
                'post_modified_gmt' => gmdate('Y-m-d H:i:s', $unix_time),
            ),
            array('ID' => $post_id)
        );

    }

    /**
     *  delete va post
     */
    static function post_del_va($va_post_id)
    {
        global $wpdb;
        $table_name = $wpdb->get_blog_prefix() . 'abv_viravi';
        $wpdb->delete($table_name, array('id' => $va_post_id), array('%d'));
    }

    /**
     * get fp posted
     */
    static function get_fp_posted($fp_id)
    {
        global $wpdb;
        $table_name = $wpdb->get_blog_prefix() . 'abv_viravi';
        $time = Functions::get_time_gmt();
        $res = $wpdb->get_results($wpdb->prepare("
                    SELECT COUNT(*) FROM $table_name WHERE fanpage = '%d' and fb_id <> '' and date < '%d'
                    ", [
                $fp_id,
                $time
            ]
        ), ARRAY_A);
        return $res[0]['COUNT(*)'];

    }


    /**
     * update post
     */

    static function update_va_post($id_va_post)
    {
        global $wpdb;
        $table_name = $wpdb->get_blog_prefix() . 'abv_viravi';
        $res = $wpdb->update($table_name,
            array('date' => Functions::get_time_gmt(), 'posted' => 1),
            array('id' => $id_va_post)
        );
        return $res;
    }

    /**
     * get all saved yt ids in array
     */
    static function get_all_yt_id()
    {
        $posts = Db::get_all_posts();
        if ($posts) {
            $posts = wp_list_pluck($posts, 'url');
            $arr = [];
            foreach ($posts as $item) {
                $arr[] = end(explode('/', $item));
            }
            return $arr;
        }
        return false;
    }
}