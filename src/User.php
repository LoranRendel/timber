<?php

namespace Timber;

use WP_User;

/**
 * Class User
 *
 * A user object represents a WordPress user.
 *
 * The currently logged-in user will be available as `{{ user }}` in your Twig files through the
 * global context. If a user is not logged in, it will be `false`. This will make it possible for
 * you to check if a user is logged by checking for `user` instead of calling `is_user_logged_in()`
 * in your Twig templates.
 *
 * @api
 * @example
 * ```twig
 * {% if user %}
 *     Hello {{ user.name }}
 * {% endif %}
 * ```
 *
 * The difference between a logged-in user and a post author:
 *
 * ```php
 * $context = Timber::context();
 *
 * Timber::render( 'single.twig', $context );
 * ```
 * ```twig
 * <p class="current-user-info">Your name is {{ user.name }}</p>
 * <p class="article-info">This article is called "{{ post.title }}"
 *     and it’s by {{ post.author.name }}</p>
 * ```
 * ```html
 * <p class="current-user-info">Your name is Jesse Eisenberg</p>
 * <p class="article-info">This article is called "Consider the Lobster"
 *     and it’s by David Foster Wallace</p>
 * ```
 */
class User extends CoreEntity
{
    /**
     * The underlying WordPress Core object.
     *
     * @since 2.0.0
     *
     * @var \WP_User|null
     */
    protected ?WP_User $wp_object;

    public $object_type = 'user';

    public static $representation = 'user';

    public $_link;

    /**
     * @api
     * @var string A URL to an avatar that overrides anything from Gravatar, etc.
     */
    public $avatar_override;

    /**
     * @api
     * @var string The description from WordPress
     */
    public $description;

    /**
     * @api
     * @var string
     */
    public $display_name = '';

    /**
     * @api
     * @var string The first name of the user
     */
    public $first_name;

    /**
     * @api
     * @var string The last name of the user
     */
    public $last_name;

    /**
     * @api
     * @var int The ID from WordPress
     */
    public $id;

    /**
     * @api
     * @var string
     */
    public $user_nicename;

    /**
     * The roles the user is part of.
     *
     * @api
     * @since 1.8.5
     *
     * @var array
     */
    protected $roles;

    /**
     * Construct a User object. For internal use only: Do not call directly.
     * Call `Timber::get_user()` instead.
     *
     * @internal
     */
    final protected function __construct()
    {
    }

    /**
     * Build a new User object.
     */
    public static function build(WP_User $wp_user): self
    {
        $user = new static();
        $user->init($wp_user);

        return $user;
    }

    /**
     * @api
     * @example
     * ```twig
     * This post is by {{ post.author }}
     * ```
     * ```html
     * This post is by Jared Novack
     * ```
     *
     * @return string a fallback for Timber\User::name()
     */
    public function __toString()
    {
        return $this->name();
    }

    /**
     * @internal
     */
    protected function init($wp_user)
    {
        $this->wp_object = $wp_user;

        $data = get_userdata($wp_user->ID);
        if (!isset($data->data)) {
            return;
        }
        $this->import($data->data);

        if (isset($data->roles)) {
            $this->roles = $this->get_roles($data->roles);
        }

        // Never leak password data
        unset($this->user_pass);
        $this->id = $this->ID = (int) $wp_user->ID;
    }

    /**
     * Gets the underlying WordPress Core object.
     *
     * @since 2.0.0
     *
     * @return \WP_User|null
     */
    public function wp_object(): ?WP_User
    {
        return $this->wp_object;
    }

    /**
     * Get the URL of the user's profile
     *
     * @api
     * @return string http://example.org/author/lincoln
     */
    public function link()
    {
        if (!$this->_link) {
            $this->_link = user_trailingslashit(get_author_posts_url($this->ID));
        }
        return $this->_link;
    }

    /**
     * Gets a user meta value.
     *
     * @api
     * @deprecated 2.0.0, use `{{ user.meta('field_name') }}` instead.
     * @see \Timber\User::meta()
     *
     * @param string $field_name The field name for which you want to get the value.
     * @return mixed The meta field value.
     */
    public function get_field($field_name = null)
    {
        Helper::deprecated(
            "{{ user.get_field('field_name') }}",
            "{{ user.meta('field_name') }}",
            '2.0.0'
        );

        return $this->meta($field_name);
    }

    /**
     * Get the name of the User
     *
     * @api
     * @return string the human-friendly name of the user (ex: "Buster Bluth")
     */
    public function name()
    {
        /**
         * Filters the name of a user.
         *
         * @since 1.1.4
         *
         * @param string       $name The name of the user. Default `display_name`.
         * @param \Timber\User $user The user object.
         */
        return apply_filters('timber/user/name', $this->display_name, $this);
    }

    /**
     * Get the relative path to the user's profile
     *
     * @api
     * @return string ex: /author/lincoln
     */
    public function path()
    {
        return URLHelper::get_rel_url($this->link());
    }

    /**
     * @api
     * @return string ex baberaham-lincoln
     */
    public function slug()
    {
        return $this->user_nicename;
    }

    /**
     * Gets a user meta value.
     *
     * @api
     * @deprecated 2.0.0, use `{{ user.meta('field_name') }}` instead.
     *
     * @param string $field_name The field name for which you want to get the value.
     * @return mixed The meta field value.
     */
    public function get_meta_field($field_name)
    {
        Helper::deprecated(
            "{{ user.get_meta_field('field_name') }}",
            "{{ user.meta('field_name') }}",
            '2.0.0'
        );

        return $this->meta($field_name);
    }

    /**
     * Gets a user meta value.
     *
     * @api
     * @deprecated 2.0.0, use `{{ user.meta('field_name') }}` instead.
     *
     * @param string $field_name The field name for which you want to get the value.
     * @return mixed The meta field value.
     */
    public function get_meta($field_name)
    {
        Helper::deprecated(
            "{{ user.get_meta('field_name') }}",
            "{{ user.meta('field_name') }}",
            '2.0.0'
        );
        return $this->meta($field_name);
    }

    /**
       * Creates an associative array with user role slugs and their translated names.
       *
       * @internal
       * @since 1.8.5
       * @param array $roles user roles.
       * @return array|null
       */
    protected function get_roles($roles)
    {
        if (empty($roles)) {
            // @codeCoverageIgnoreStart
            return null;
            // @codeCoverageIgnoreEnd
        }

        $wp_roles = wp_roles();
        $names = $wp_roles->get_names();

        $values = [];

        foreach ($roles as $role) {
            $name = $role;
            if (isset($names[$role])) {
                $name = translate_user_role($names[$role]);
            }
            $values[$role] = $name;
        }

        return $values;
    }

    /**
     * Gets the user roles.
     * Roles shouldn’t be used to check whether a user has a capability. Use roles only for
     * displaying purposes. For example, if you want to display the name of the subscription a user
     * has on the site behind a paywall.
     *
     * If you want to check for capabilities, use `{{ user.can('capability') }}`. If you only want
     * to check whether a user is logged in, you can use `{% if user %}`.
     *
     * @api
     * @since 1.8.5
     * @example
     * ```twig
     * <h2>Role name</h2>
     * {% for role in post.author.roles %}
     *     {{ role }}
     * {% endfor %}
     * ```
     * ```twig
     * <h2>Role name</h2>
     * {{ post.author.roles|join(', ') }}
     * ```
     * ```twig
     * {% for slug, name in post.author.roles %}
     *     {{ slug }}
     * {% endfor %}
     * ```
     *
     * @return array|null
     */
    public function roles()
    {
        return $this->roles;
    }

    /**
     * Checks whether a user has a capability.
     *
     * Don’t use role slugs for capability checks. While checking against a role in place of a
     * capability is supported in part, this practice is discouraged as it may produce unreliable
     * results. This includes cases where you want to check whether a user is registered. If you
     * want to check whether a user is a Subscriber, use `{{ user.can('read') }}`. If you only want
     * to check whether a user is logged in, you can use `{% if user %}`.
     *
     * @api
     * @since 1.8.5
     *
     * @param string $capability The capability to check.
     *
     * @example
     * Give moderation users another CSS class to style them differently.
     *
     * ```twig
     * <span class="comment-author {{ comment.author.can('moderate_comments') ? 'comment-author--is-moderator }}">
     *     {{ comment.author.name }}
     * </span>
     * ```
     *
     * @return bool Whether the user has the capability.
     */
    public function can($capability)
    {
        return user_can($this->ID, $capability);
    }

    /**
     * Gets a user’s avatar URL.
     *
     * @api
     * @since 1.9.1
     * @example
     * Get a user avatar with a width and height of 150px:
     *
     * ```twig
     * <img src="{{ post.author.avatar({ size: 150 }) }}">
     * ```
     *
     * @param null|array $args Parameters for
     *                         [`get_avatar_url()`](https://developer.wordpress.org/reference/functions/get_avatar_url/).
     * @return string The avatar URL.
     */
    public function avatar($args = null)
    {
        if ($this->avatar_override) {
            return $this->avatar_override;
        }

        return get_avatar_url($this->id, $args);
    }
}
