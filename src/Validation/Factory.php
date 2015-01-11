<?php

/**
 * Deep
 *
 * @package      rsanchez\Deep
 * @author       Rob Sanchez <info@robsanchez.com>
 */

namespace rsanchez\Deep\Validation;

use rsanchez\Deep\Model\AbstractProperty;
use Illuminate\Validation\Factory as IlluminateFactory;

class Factory extends IlluminateFactory
{
    /**
     * List of validation messages
     * @var array
     */
    protected $defaultMessages = [
        'accepted' => 'The :attribute must be accepted.',
        'active_url' => 'The :attribute is not a valid URL.',
        'after' => 'The :attribute must be a date after :date.',
        'alpha' => 'The :attribute may only contain letters.',
        'alpha_dash' => 'The :attribute may only contain letters, numbers, and dashes.',
        'alpha_num' => 'The :attribute may only contain letters and numbers.',
        'array' => 'The :attribute must be an array.',
        'before' => 'The :attribute must be a date before :date.',
        'between' => [
            'numeric' => 'The :attribute must be between :min and :max.',
            'file' => 'The :attribute must be between :min and :max kilobytes.',
            'string' => 'The :attribute must be between :min and :max characters.',
            'array' => 'The :attribute must have between :min and :max items.',
        ],
        'boolean' => 'The :attribute field must be true or false.',
        'confirmed' => 'The :attribute confirmation does not match.',
        'date' => 'The :attribute is not a valid date.',
        'date_format' => 'The :attribute does not match the format :format.',
        'different' => 'The :attribute and :other must be different.',
        'digits' => 'The :attribute must be :digits digits.',
        'digits_between' => 'The :attribute must be between :min and :max digits.',
        'email' => 'The :attribute must be a valid email address.',
        'exists' => 'The selected :attribute is invalid.',
        'image' => 'The :attribute must be an image.',
        'in' => 'The selected :attribute is invalid.',
        'integer' => 'The :attribute must be an integer.',
        'ip' => 'The :attribute must be a valid IP address.',
        'max' => [
            'numeric' => 'The :attribute may not be greater than :max.',
            'file' => 'The :attribute may not be greater than :max kilobytes.',
            'string' => 'The :attribute may not be greater than :max characters.',
            'array' => 'The :attribute may not have more than :max items.',
        ],
        'mimes' => 'The :attribute must be a file of type: :values.',
        'min' => [
            'numeric' => 'The :attribute must be at least :min.',
            'file' => 'The :attribute must be at least :min kilobytes.',
            'string' => 'The :attribute must be at least :min characters.',
            'array' => 'The :attribute must have at least :min items.',
        ],
        'not_in' => 'The selected :attribute is invalid.',
        'numeric' => 'The :attribute must be a number.',
        'regex' => 'The :attribute format is invalid.',
        'required' => 'The :attribute field is required.',
        'required_if' => 'The :attribute field is required when :other is :value.',
        'required_with' => 'The :attribute field is required when :values is present.',
        'required_with_all' => 'The :attribute field is required when :values is present.',
        'required_without' => 'The :attribute field is required when :values is not present.',
        'required_without_all' => 'The :attribute field is required when none of :values are present.',
        'same' => 'The :attribute and :other must match.',
        'size' => [
            'numeric' => 'The :attribute must be :size.',
            'file' => 'The :attribute must be :size kilobytes.',
            'string' => 'The :attribute must be :size characters.',
            'array' => 'The :attribute must contain :size items.',
        ],
        'unique' => 'The :attribute has already been taken.',
        'url' => 'The :attribute format is invalid.',
        'timezone' => 'The :attribute must be a valid zone.',
        /**/
        'yes_or_no' => 'The :attribute field must be y or n.',
        /**/
        'custom' => [
            /*
            'attribute-name' => array(
                'rule-name' => 'custom-message',
            ),
            */
        ],
        'attributes' => [],
    ];

    protected $propertyValidators = [
        'select' => 'ListItemsValidator',
    ];

    /**
     * Create a new Validator instance.
     *
     * @param  array  $data
     * @param  array  $rules
     * @param  array  $messages
     * @param  array  $customAttributes
     * @return \Illuminate\Validation\Validator
     */
    public function make(array $data, array $rules, array $messages = array(), array $customAttributes = array())
    {
        $messages = array_merge($this->defaultMessages, $messages);

        return parent::make($data, $rules, $messages, $customAttributes);
    }

    /**
     * Resolve a new Validator instance.
     *
     * @param  array  $data
     * @param  array  $rules
     * @param  array  $messages
     * @param  array  $customAttributes
     * @return \Illuminate\Validation\Validator
     */
    protected function resolve(array $data, array $rules, array $messages, array $customAttributes)
    {
        if (is_null($this->resolver))
        {
            return new Validator($this->translator, $data, $rules, $messages, $customAttributes);
        }

        return call_user_func($this->resolver, $this->translator, $data, $rules, $messages, $customAttributes);
    }

    public function hasPropertyValidator(AbstractProperty $property)
    {
        return isset($this->propertyValidators[$property->getType()]);
    }

    public function makePropertyValidator(AbstractProperty $property)
    {
        $type = $property->getType();

        if (! isset($this->propertyValidators[$type])) {
            return null;
        }

        $validatorClass = '\\rsanchez\\Deep\\Validation\\'.$this->propertyValidators[$type];

        return new $validatorClass();
    }
}
