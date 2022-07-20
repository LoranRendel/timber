<?php

namespace Timber;

use ArrayObject;
use JsonSerializable;
use WP_Query;

/**
 * Class PostQuery
 *
 * Query for a collection of WordPress posts.
 *
 * This is the equivalent of using `WP_Query` in normal WordPress development.
 *
 * PostQuery is used directly in Twig templates to iterate through post query results and
 * retrieve meta information about them.
 *
 * @api
 */
class PostQuery extends ArrayObject implements PostCollectionInterface, JsonSerializable
{
    use AccessesPostsLazily;

    /**
     * Found posts.
     *
     * The total amount of posts found for this query. Will be `0` if you used `no_found_rows` as a
     * query parameter. Will be `null` if you passed in an existing collection of posts.
     *
     * @api
     * @since 1.11.1
     * @var int The amount of posts found in the query.
     */
    public $found_posts = null;

    /**
     * If the user passed an array, it is stored here.
     *
     * @var array
     */
    protected $userQuery;

    /**
     * The internal WP_Query instance that this object is wrapping.
     *
     * @var \WP_Query
     */
    protected $wp_query = null;

    protected $pagination = null;

    /**
     * Query for a collection of WordPress posts.
     *
     * Refer to the official documentation for
     * [WP_Query](https://developer.wordpress.org/reference/classes/wp_query/) for a list of all
     * the arguments that can be used for the `$query` parameter.
     *
     * @api
     * @example
     * ```php
     * // Get posts from default query.
     * global $wp_query;
     *
     * $posts = Timber::get_posts( $wp_query );
     *
     * // Using the WP_Query argument format.
     * $posts = Timber::get_posts( [
     *     'post_type'     => 'article',
     *     'category_name' => 'sports',
     * ] );
     *
     * // Passing a WP_Query instance.
     * $posts = Timber::get_posts( new WP_Query( [ 'post_type' => 'any' ) );
     * ```
     *
     * @param WP_Query $query The WP_Query object to wrap.
     */
    public function __construct(WP_Query $query)
    {
        $this->wp_query = $query;
        $this->found_posts = $this->wp_query->found_posts;

        $posts = $this->wp_query->posts ?: [];

        parent::__construct($posts, 0, PostsIterator::class);
    }

    /**
     * Get pagination for a post collection.
     *
     * Refer to the [Pagination Guide]({{< relref "../guides/pagination.md" >}}) for a detailed usage example.
     *
     * Optionally could be used to get pagination with custom preferences.
     *
     * @api
     * @example
     * ```twig
     * {% if posts.pagination.prev %}
     *     <a href="{{ posts.pagination.prev.link }}">Prev</a>
     * {% endif %}
     *
     * <ul class="pages">
     *     {% for page in posts.pagination.pages %}
     *         <li>
     *             <a href="{{ page.link }}" class="{{ page.class }}">{{ page.title }}</a>
     *         </li>
     *     {% endfor %}
     * </ul>
     *
     * {% if posts.pagination.next %}
     *     <a href="{{ posts.pagination.next.link }}">Next</a>
     * {% endif %}
     * ```
     *
     * @param array $prefs Optional. Custom preferences. Default `array()`.
     *
     * @return \Timber\Pagination object
     */
    public function pagination($prefs = [])
    {
        if (!$this->pagination && $this->wp_query instanceof \WP_Query) {
            $this->pagination = new Pagination($prefs, $this->wp_query);
        }

        return $this->pagination;
    }

    /**
     * Gets the original query used to get a collection of Timber posts.
     *
     * @since 2.0
     * @return WP_Query|null
     */
    public function query()
    {
        return $this->wp_query;
    }

    /**
     * Override data printed by var_dump() and similar. Realizes the collection before
     * returning. Due to a PHP bug, this only works in PHP >= 7.4.
     *
     * @see https://bugs.php.net/bug.php?id=69264
     * @internal
     */
    public function __debugInfo(): array
    {
        return [
            'info' => sprintf(
                '
********************************************************************************

    This output is generated by %s().

    The properties you see here are not actual properties, but only debug
    output. If you want to access the actual instances of Timber\Posts, loop
        over the collection or get all posts through $query->to_array().

        More info: https://timber.github.io/docs/v2/guides/posts/#debugging-post-collections

********************************************************************************',
                __METHOD__
            ),
            'posts' => $this->getArrayCopy(),
            'wp_query' => $this->wp_query,
            'found_posts' => $this->found_posts,
            'pagination' => $this->pagination,
            'factory' => $this->factory,
            'iterator' => $this->getIterator(),
        ];
    }

    /**
     * Returns realized (eagerly instantiated) Timber\Post data to serialize to JSON.
     *
     * @internal
     */
    #[\ReturnTypeWillChange]
    public function jsonSerialize()
    {
        return $this->getArrayCopy();
    }
}
