<?php

namespace rsanchez\Deep\Model;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use rsanchez\Deep\Model\Channel;
use rsanchez\Deep\Collection\EntryCollection;
use DateTime;

class Entry extends Model
{
    /**
     * {@inheritdoc}
     *
     * @var string
     */
    protected $table = 'channel_titles';

    /**
     * {@inheritdoc}
     *
     * @var string
     */
    protected $primaryKey = 'entry_id';

    /**
     * Custom fields, keyed by name
     *   field_name => \rsanchez\Deep\Model\Field
     * @var array
     */
    protected $fieldsByName = array();

    /**
     * Join tables
     * @var array
     */
    protected static $tables = array(
        'members' => array('members.member_id', 'channel_titles.author_id'),
        'channels' => array('channels.channel_id', 'channel_titles.channel_id'),
    );

    /**
     * Define the Channel Eloquent relationship
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function channel()
    {
        return $this->belongsTo('\\rsanchez\\Deep\\Model\\Channel');
    }

    /**
     * {@inheritdoc}
     *
     * Joins with the channel data table, and eager load channels, fields and fieldtypes
     *
     * @param  boolean                               $excludeDeleted
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function newQuery($excludeDeleted = true)
    {
        $query = parent::newQuery($excludeDeleted);

        $query->with('channel', 'channel.fields', 'channel.fields.fieldtype');

        $query->join('channel_data', 'channel_titles.entry_id', '=', 'channel_data.entry_id');

        return $query;
    }

    /**
     * {@inheritdoc}
     *
     * Hydrate the collection after creation
     *
     * @param  array                                     $models
     * @return \rsanchez\Deep\Collection\EntryCollection
     */
    public function newCollection(array $models = array())
    {
        $collection = new EntryCollection($models);

        if ($models) {
            $collection->hydrate();
        }

        return $collection;
    }

    /**
     * {@inheritdoc}
     *
     * Get custom field value, if key is a custom field name
     *
     * @param  string $key
     * @return mixed
     */
    public function getAttribute($key)
    {
        $hasAttribute = array_key_exists($key, $this->attributes);
        $hasChannel = isset($this->channel) && isset($this->channel->fields);

        if (! $hasAttribute && $hasChannel && $this->channel->fields->hasField($key)) {
            $this->attributes[$key] = $this->attributes['field_id_'.$this->channel->fields->getFieldId($key)];
        }

        return parent::getAttribute($key);
    }

    /**
     * Get the entry_date column as a DateTime object
     *
     * @param  int       $value unix time
     * @return \DateTime
     */
    public function getEntryDateAttribute($value)
    {
        return DateTime::createFromFormat('U', $value);
    }

    /**
     * Get the expiration_date column as a DateTime object, or null if there is no expiration date
     *
     * @param  int            $value unix time
     * @return \DateTime|null
     */
    public function getExpirationDateAttribute($value)
    {
        return $value ? DateTime::createFromFormat('U', $value) : null;
    }

    /**
     * Get the comment_expiration_date column as a DateTime object, or null if there is no expiration date
     *
     * @param  int            $value unix time
     * @return \DateTime|null
     */
    public function getCommentExpirationDateAttribute($value)
    {
        return $value ? DateTime::createFromFormat('U', $value) : null;
    }

    /**
     * Get the recent_comment_date column as a DateTime object, or null if there is no expiration date
     *
     * @param  int            $value unix time
     * @return \DateTime|null
     */
    public function getRecentCommentDateAttribute($value)
    {
        return $value ? DateTime::createFromFormat('U', $value) : null;
    }

    /**
     * Get the edit_date column as a DateTime object
     *
     * @param  int       $value unix time
     * @return \DateTime
     */
    public function getEditDateAttribute($value)
    {
        return DateTime::createFromFormat('YmdHis', $value);
    }

    /**
     * Save the entry (not yet supported)
     *
     * @param  array $options
     * @return void
     */
    public function save(array $options = array())
    {
        throw new \Exception('Saving is not supported');
    }

    /**
     * Filter by Entry Status
     *
     * @param  \Illuminate\Database\Eloquent\Builder $query
     * @param  string|array                          $status
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeStatus(Builder $query, $status)
    {
        $status = is_array($status) ? $status : array($status);

        return $query->whereIn('channel_titles.status', $status);
    }

    /**
     * Filter by Channel Name
     *
     * @param  \Illuminate\Database\Eloquent\Builder $query
     * @param  string|array                          $channelName
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeChannelName(Builder $query, $channelName)
    {
        $channelName = is_array($channelName) ? $channelName : array($channelName);

        return $this->requireTable($query, 'channels')->whereIn('channels.channel_name', $channelName);
    }

    /**
     * Filter by Channel ID
     *
     * @param  \Illuminate\Database\Eloquent\Builder $query
     * @param  int|array                             $channelId
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeChannelId(Builder $query, $channelId)
    {
        $channelId = is_array($channelId) ? $channelId : array($channelId);

        return $query->whereIn('channel_titles.channel_id', $channelId);
    }

    /**
     * Filter by Author ID
     *
     * @param  \Illuminate\Database\Eloquent\Builder $query
     * @param  int|array                             $authorId
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeAuthorId(Builder $query, $authorId)
    {
        $authorId = is_array($authorId) ? $authorId : array($authorId);

        return $query->whereIn('channel_titles.author_id', $authorId);
    }

    /**
     * Filter out Expired Entries
     *
     * @param  \Illuminate\Database\Eloquent\Builder $query
     * @param  bool                                  $showExpired
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeShowExpired(Builder $query, $showExpired = true)
    {
        if (! $showExpired) {
            $query->whereRaw(
                "(`{$prefix}channel_titles`.`expiration_date` = '' OR  `{$prefix}channel_titles`.`expiration_date` > NOW())"
            );
        }

        return $query;
    }

    /**
     * Filter out Future Entries
     *
     * @param  \Illuminate\Database\Eloquent\Builder $query
     * @param  bool                                  $showFutureEntries
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeShowFutureEntries(Builder $query, $showFutureEntries = true)
    {
        if (! $showFutureEntries) {
            $query->where('channel_titles.entry_date', '<=', time());
        }

        return $query;
    }

    /**
     * Set a Fixed Order
     *
     * @param  \Illuminate\Database\Eloquent\Builder $query
     * @param  array                                 $fixedOrder
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeFixedOrder(Builder $query, array $fixedOrder)
    {
        return $this->scopeEntryId($query, $fixedOrder)
                    ->orderBy('FIELD('.implode(', ', $fixedOrder).')', 'asc');
    }

    /**
     * Set Sticky Entries to appear first
     *
     * @param  \Illuminate\Database\Eloquent\Builder $query
     * @param  bool                                  $sticky
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeSticky(Builder $query, $sticky = true)
    {
        if ($sticky) {
            array_unshift($query->getQuery()->orders, array('channel_titles.sticky', 'desc'));
        }

        return $query;
    }

    /**
     * Filter by Entry ID
     *
     * @param  \Illuminate\Database\Eloquent\Builder $query
     * @param  string|array                          $entryId
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeEntryId(Builder $query, $entryId)
    {
        $entryId = is_array($entryId) ? $entryId : array($entryId);

        return $query->whereIn('channel_titles.entry_id', $entryId);
    }

    /**
     * Filter by Not Entry ID
     *
     * @param  \Illuminate\Database\Eloquent\Builder $query
     * @param  string|array                          $notEntryId
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeNotEntryId(Builder $query, $notEntryId)
    {
        $notEntryId = is_array($notEntryId) ? $notEntryId : array($notEntryId);

        return $query->whereNotIn('channel_titles.entry_id', $notEntryId);
    }

    /**
     * Filter out entries before the specified Entry ID
     *
     * @param  \Illuminate\Database\Eloquent\Builder $query
     * @param  int                                   $entryIdFrom
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeEntryIdFrom(Builder $query, $entryIdFrom)
    {
        return $query->where('channel_titles.entry_id', '>=', $entryIdFrom);
    }

    /**
     * Filter out entries after the specified Entry ID
     *
     * @param  \Illuminate\Database\Eloquent\Builder $query
     * @param  int                                   $entryIdTo
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeEntryIdTo(Builder $query, $entryIdTo)
    {
        return $query->where('channel_titles.entry_id', '<=', $entryIdTo);
    }

    /**
     * Filter by Member Group ID
     *
     * @param  \Illuminate\Database\Eloquent\Builder $query
     * @param  int|array                             $groupId
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeGroupId(Builder $query, $groupId)
    {
        $groupId = is_array($groupId) ? $groupId : array($groupId);

        return $this->requireTable($query, 'members')->whereIn('members.group_id', $groupId);
    }

    /**
     * Filter by Not Member Group ID
     *
     * @param  \Illuminate\Database\Eloquent\Builder $query
     * @param  int|array                             $notGroupId
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeNotGroupId(Builder $query, $notGroupId)
    {
        $notGroupId = is_array($notGroupId) ? $notGroupId : array($notGroupId);

        return $this->requireTable($query, 'members')->whereNotIn('members.group_id', $notGroupId);
    }

    /**
     * Limit the number of results
     *
     * @param  \Illuminate\Database\Eloquent\Builder $query
     * @param  int                                   $limit
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeLimit(Builder $query, $limit)
    {
        return $query->take($limit);
    }

    /**
     * Offset the results
     *
     * @param  \Illuminate\Database\Eloquent\Builder $query
     * @param  int                                   $offset
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeOffset(Builder $query, $offset)
    {
        return $query->skip($offset);
    }

    /**
     * Filter out entries before the specified date
     *
     * @param  \Illuminate\Database\Eloquent\Builder $query
     * @param  int|DateTime                          $startOn unix time
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeStartOn(Builder $query, $startOn)
    {
        if ($startOn instanceof DateTime) {
            $startOn = $startOn->format('U');
        }

        return $query->where('channel_titles.entry_date', '>=', $startOn);
    }

    /**
     * Filter out entries after the specified date
     *
     * @param  \Illuminate\Database\Eloquent\Builder $query
     * @param  int|DateTime                          $startOn unix time
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeStopBefore(Builder $query, $stopBefore)
    {
        if ($stopBefore instanceof DateTime) {
            $stopBefore = $stopBefore->format('U');
        }

        return $query->where('channel_titles.entry_date', '<', $stopBefore);
    }

    /**
     * Filter by URL Title
     *
     * @param  \Illuminate\Database\Eloquent\Builder $query
     * @param  string|array                          $urlTitle
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeUrlTitle(Builder $query, $urlTitle)
    {
        $urlTitle = is_array($urlTitle) ? $urlTitle : array($urlTitle);

        return $query->whereIn('channel_titles.url_title', $urlTitle);
    }

    /**
     * Filter by Member Username
     *
     * @param  \Illuminate\Database\Eloquent\Builder $query
     * @param  string|array                          $username
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeUsername(Builder $query, $username)
    {
        $username = is_array($username) ? $username : array($username);

        return $this->requireTable($query, 'members')->whereIn('members.username', $username);
    }

    /**
     * Filter by Custom Field Search
     * @TODO how to get custom field names
     */
    public function scopeSearch(Builder $query, array $search)
    {
        $this->requireTable($query, 'channel_data');

        foreach ($search as $fieldName => $values) {
            try {
                $field = $this->channelFieldRepository->find($fieldName);

                $query->where(function ($query) use ($field, $values) {

                    foreach ($values as $value) {
                        $query->orWhere('channel_data.field_id_'.$field->id(), 'LIKE', '%{$value}%');
                    }

                });

            } catch (Exception $e) {
                //$e->getMessage();
            }
        }

        return $query;
    }

    /**
     * Filter by Year
     *
     * @param  \Illuminate\Database\Eloquent\Builder $query
     * @param  int                                   $year
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeYear(Builder $query, $year)
    {
        return $query->where('channel_titles.year', $year);
    }

    /**
     * Filter by Month
     *
     * @param  \Illuminate\Database\Eloquent\Builder $query
     * @param  int                                   $month
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeMonth(Builder $query, $month)
    {
        return $query->where('channel_titles.month', $month);
    }

    /**
     * Filter by Day
     *
     * @param  \Illuminate\Database\Eloquent\Builder $query
     * @param  int                                   $day
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeDay(Builder $query, $day)
    {
        return $query->where('channel_titles.day', $day);
    }

    /**
     * Register all custom fields with this entry
     * @return void
     */
    protected function registerFields()
    {
        static $fieldsRegistered = false;

        if ($fieldsRegistered === false && isset($this->channel) && isset($this->channel->fields)) {
            $fieldsRegistered = true;

            $fieldsByName =& $this->fieldsByName;

            $this->channel->fields->each(function ($field) use (&$fieldsByName) {
                $fieldsByName[$field->field_name] = $field;
            });
        }
    }

    /**
     * Join the required table, once
     *
     * @param  \Illuminate\Database\Eloquent\Builder $query
     * @param  string                                $which table name
     * @return \Illuminate\Database\Eloquent\Builder $query
     */
    protected function requireTable(Builder $query, $which)
    {
        if (! isset(static::$tables[$which])) {
            return $query;
        }

        foreach ($query->getQuery()->joins as $joinClause) {
            if ($joinClause->table === $which) {
                return $query;
            }
        }

        return $query->join($which, static::$tables[$which][0], '=', static::$tables[$which][1]);
    }
}
