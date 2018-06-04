<?php

namespace Themosis\Forms\Fields\Types;

use Themosis\Forms\Contracts\FieldTypeInterface;
use Themosis\Forms\Contracts\FormInterface;
use Themosis\Html\HtmlBuilder;

abstract class BaseType extends HtmlBuilder implements \ArrayAccess, \Countable, FieldTypeInterface
{
    /**
     * List of options.
     *
     * @var array
     */
    protected $options;

    /**
     * List of allowed options.
     *
     * @var array
     */
    protected $allowedOptions = [
        'group',
        'rules',
        'messages',
        'placeholder',
        'attributes',
        'label',
        'label_attr'
    ];

    /**
     * List of default options per field.
     *
     * @var array
     */
    protected $defaultOptions = [
        'group' => 'default',
        'rules' => [],
        'messages' => [],
        'attributes' => [],
        'label_attr' => []
    ];

    /**
     * Field name prefix.
     * Applied automatically to avoid conflicts with core query variables.
     *
     * @var string
     */
    protected $prefix = 'th_';

    /**
     * The field basename.
     * Name property without the prefix as defined by the user.
     *
     * @var string
     */
    protected $baseName;

    /**
     * Field validation rules.
     *
     * @var array
     */
    protected $rules = [];

    /**
     * A list of custom error messages
     * by field rules.
     *
     * @var array
     */
    protected $messages = [];

    /**
     * A custom :attribute
     * placeholder value.
     *
     * @var string
     */
    protected $placeholder;

    /**
     * The field label display title.
     *
     * @var string
     */
    protected $label;

    /**
     * The form instance handling the field.
     *
     * @var FormInterface
     */
    protected $form;

    /**
     * Indicates if form is rendered.
     *
     * @var bool
     */
    protected $rendered = false;

    /**
     * The field view.
     *
     * @var string
     */
    protected $view;

    /**
     * BaseType constructor.
     *
     * @param string $name
     */
    public function __construct(string $name)
    {
        parent::__construct();
        $this->baseName = $name;
        $this->prefixName($name);
    }

    /**
     * Return the list of default options.
     *
     * @return array
     */
    public function getDefaultOptions(): array
    {
        // Setup default validation rules.
        $this->defaultOptions['rules'] = $this->rules;

        // Setup default messages.
        $this->defaultOptions['messages'] = $this->messages;

        // Setup default placeholder.
        $this->defaultOptions['placeholder'] = $this->placeholder ?? $this->getBaseName();

        // Setup default label.
        $this->defaultOptions['label'] = $this->label ?? ucfirst(str_replace(['-', '_'], ' ', $this->getBaseName()));

        return $this->defaultOptions;
    }

    /**
     * Return allowed options for the field.
     *
     * @return array
     */
    public function getAllowedOptions(): array
    {
        return $this->allowedOptions;
    }

    /**
     * Prefix the field name property.
     *
     * @param string $name The name property value (base name).
     *
     * @return $this
     */
    protected function prefixName(string $name): FieldTypeInterface
    {
        $this->options['name'] = trim($this->prefix).$name;

        return $this;
    }

    /**
     * Set field options.
     *
     * @param array $options
     *
     * @return FieldTypeInterface
     */
    public function setOptions(array $options): FieldTypeInterface
    {
        // A user cannot override the "name" property.
        if (isset($options['name'])) {
            throw new \InvalidArgumentException('The "name" option can not be overridden.');
        }

        $this->options = $this->parseOptions(array_merge(
            $this->getDefaultOptions(),
            $this->options,
            $options
        ));

        return $this;
    }

    /**
     * Parse and setup some default options if not set.
     *
     * @param array $options
     *
     * @return array
     */
    protected function parseOptions(array $options)
    {
        // Set a default "id" attribute. This attribute can be used on the field
        // and to its associated label as the "for" attribute value if not set.
        if (! isset($options['attributes']['id'])) {
            $options['attributes']['id'] = $this->getName().'_field';
        }

        // Set the "for" attribute automatically on the label attributes property.
        if (! isset($options['label_attr']['for'])) {
            $options['label_attr']['for'] = $options['attributes']['id'];
        }

        return $options;
    }

    /**
     * Return field options.
     *
     * @param string $optionKey Optional. Retrieve all options by default or the value based on given option key.
     *
     * @return mixed
     */
    public function getOptions(string $optionKey = '')
    {
        return $this->options[$optionKey] ?? $this->options;
    }

    /**
     * Set the field prefix.
     *
     * @param string $prefix
     *
     * @return FieldTypeInterface
     */
    public function setPrefix(string $prefix): FieldTypeInterface
    {
        $this->prefix = $prefix;

        // Automatically update the "name" option based
        // on the new prefix.
        $this->prefixName($this->getBaseName());

        return $this;
    }

    /**
     * Return the field prefix.
     *
     * @return string
     */
    public function getPrefix(): string
    {
        return $this->prefix;
    }

    /**
     * Return the field name property value.
     *
     * @return string
     */
    public function getName(): string
    {
        return $this->getOptions('name');
    }

    /**
     * Return the field basename.
     *
     * @return string
     */
    public function getBaseName(): string
    {
        return $this->baseName;
    }

    /**
     * Return the field attributes.
     *
     * @return array
     */
    public function getAttributes()
    {
        return $this->getOptions('attributes');
    }

    /**
     * Get the value of a defined attribute.
     *
     * @param string $name
     *
     * @return mixed
     */
    public function getAttribute(string $name)
    {
        $atts = $this->getAttributes();

        return $atts[$name] ?? '';
    }

    /**
     * Set the field attributes.
     *
     * @param array $attributes
     *
     * @return FieldTypeInterface
     */
    public function setAttributes(array $attributes)
    {
        $this->options['attributes'] = $attributes;

        return $this;
    }

    /**
     * Setup the form instance handling the field.
     *
     * @param FormInterface $form
     *
     * @return FieldTypeInterface
     */
    public function setForm(FormInterface $form)
    {
        $this->form = $form;

        return $this;
    }

    /**
     * Output the entity as HTML.
     *
     * @return string
     */
    public function render(): string
    {
        $view = $this->form->getViewer()->make($this->getView(), $this->getFieldData());

        // Indicates that the form has been rendered at least once.
        // Then return its content.
        $this->rendered = true;

        return $view->render();
    }

    /**
     * Generate and get field data.
     *
     * @return array
     */
    protected function getFieldData(): array
    {
        return [
            '__field' => $this
        ];
    }

    /**
     * Specify the view file to use by the form.
     *
     * @param string $view
     *
     * @return FieldTypeInterface
     */
    public function setView(string $view): FieldTypeInterface
    {
        $this->view = $view;

        return $this;
    }

    /**
     * Return the view instance used by the entity.
     *
     * @return string
     */
    public function getView(): string
    {
        return $this->view;
    }

    /**
     * Indicates if the entity has been rendered or not.
     *
     * @return bool
     */
    public function isRendered(): bool
    {
        return $this->rendered;
    }

    /**
     * Generic field value getter.
     * Retrieve the field "normalized" value.
     *
     * @return mixed
     */
    public function getValue()
    {
        // TODO: Implement value() method.
    }

    /**
     * Whether a offset exists.
     *
     * @link http://php.net/manual/en/arrayaccess.offsetexists.php
     *
     * @param mixed $offset An offset to check for.
     *
     * @return bool true on success or false on failure.
     *
     * @since 5.0.0
     */
    public function offsetExists($offset)
    {
        return isset($this->options[$offset]);
    }

    /**
     * Offset to retrieve
     *
     * @link http://php.net/manual/en/arrayaccess.offsetget.php
     *
     * @param mixed $offset The offset to retrieve.
     *
     * @return mixed Can return all value types.
     *
     * @since 5.0.0
     */
    public function offsetGet($offset)
    {
        return isset($this->options[$offset]) ? $this->options[$offset] : null;
    }

    /**
     * Offset to set
     *
     * @link http://php.net/manual/en/arrayaccess.offsetset.php
     *
     * @param mixed $offset The offset to assign the value to.
     * @param mixed $value  The value to set.
     *
     * @since 5.0.0
     */
    public function offsetSet($offset, $value)
    {
        if (is_null($offset)) {
            $this->options[] = $value;
        } else {
            $this->options[$offset] = $value;
        }
    }

    /**
     * Offset to unset
     *
     * @link http://php.net/manual/en/arrayaccess.offsetunset.php
     *
     * @param mixed $offset The offset to unset.
     *
     * @since 5.0.0
     */
    public function offsetUnset($offset)
    {
        unset($this->options[$offset]);
    }

    /**
     * Count elements of an object
     *
     * @link http://php.net/manual/en/countable.count.php
     *
     * @return int The custom count as an integer.
     *
     * @since 5.1.0
     */
    public function count()
    {
        return count($this->options);
    }
}
