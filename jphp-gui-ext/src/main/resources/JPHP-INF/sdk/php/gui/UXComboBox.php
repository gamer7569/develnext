<?php
namespace php\gui;

use Traversable;

/**
 * Class UXComboBox
 * @package php\gui
 */
class UXComboBox extends UXComboBoxBase
{
    /**
     * Список.
     * @var UXList
     */
    public $items;

    /**
     * Выбранный элемент.
     * @var mixed
     */
    public $selected;

    /**
     * Выбранный индекс, начиная с 0.
     * -1 ничего не выбрано.
     * @var int
     */
    public $selectedIndex;

    /**
     * Видимое количество пунктов.
     * @var int
     */
    public $visibleRowCount;

    /**
     * @param array|Traversable $items (optional)
     */
    public function __construct($items) {}

    /**
     * @param callable|null $handler (UXListCell $cell, mixed $value, bool $empty)
     */
    public function onCellRender(callable $handler)
    {
    }

    /**
     * @param callable|null $handler (UXListCell $cell, mixed $value)
     */
    public function onButtonRender(callable $handler)
    {
    }
}