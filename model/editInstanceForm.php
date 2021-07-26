<?php

/**
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; under version 2
 * of the License (non-upgradable).
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
 *
 * Copyright (c) 2014-2021 (original work) Open Assessment Technologies SA;
 *
 */

declare(strict_types=1);

namespace oat\taoMediaManager\model;

/**
 * Service methods to manage the Media
 *
 * @access public
 * @package taoMediaManager
 */
class editInstanceForm extends \tao_actions_form_Instance
{
    protected function initForm()
    {
        parent::initForm();
        $bottom = $this->form->getActions('bottom');
        $top = $this->form->getActions('top');

        $edit = \tao_helpers_form_FormFactory::getElement('edit', 'Free');
        $value = $edit ? $this->getReplaceAssetButtonTemplate() : '';

        $edit->setValue($value);
        $top[] = $edit;
        $bottom[] = $edit;

        $this->form->setActions($bottom, 'bottom');
        $this->form->setActions($top, 'top');
    }

    private function isEnabled(): bool
    {
        return empty($this->options[self::IS_DISABLED] ?? false);
    }

    private function isReplaceEnabled(): bool
    {
        return empty($this->options['is_replace_asset_disabled'] ?? false);
    }

    private function getReplaceAssetButtonTemplate(): string
    {
        return sprintf(
            '<button 
            type="button" 
            id="edit-media" %s 
            data-classuri="%s" 
            data-uri="%s" 
            class="edit-instance btn-success small">
                <span class="icon-loop"></span>
                %s
            </button>',
            ($this->isReplaceEnabled() ? '' : 'disabled="disabled" '),
            $this->getClazz()->getUri(),
            $this->getInstance()->getUri(),
            __('Replace Asset')
        );
    }
}
