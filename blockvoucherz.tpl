{**
* Block Vouchers: module for PrestaShop 1.2-1.4
*
* @author      zapalm <zapalm@ya.ru>
* @copyright   (c) 2011-2016, zapalm
* @link        http://prestashop.modulez.ru/en/frontend-features/33-selling-gift-vouchers.html The module's homepage
* @license     http://opensource.org/licenses/afl-3.0.php Academic Free License (AFL 3.0)
*}

<!-- MODULE blockvoucherz -->
{literal}
    <STYLE TYPE="text/css">
        <!--
        .vouch_info {
            color: #DA0F00;
            font-size: 12px;
            font-weight: bold;
        }

        .vouch_end {

        }

        .vouch_sales {

        }

        .vouch_buy {
            padding-left: 30px;
        }

        .vouch_slogan {

        }
        -->
    </STYLE>
{/literal}
<div class="block">
    <h4>
        <a href="{$cat}" title="{l s='Buy the voucher' mod='blockvoucherz'}">{l s='Buy the voucher' mod='blockvoucherz'}</a>
    </h4>
    <div class="block_content">
        {if $start - $smarty.now|date_format:'%Y-%m-%d' < $days}
            <div class="vouch_info">{l s='Buy a gift voucher' mod='blockvoucherz'}</div>
            <div class="vouch_end">
                {l s='Time remains' mod='blockvoucherz'}: <b>{$days} {l s='days' mod='blockvoucherz'}</b>
            </div>
            <div class="vouch_sales">
                {l s='Was buyed' mod='blockvoucherz'}: <b>{$sales} {l s='times' mod='blockvoucherz'}</b>
            </div>
            <br/>
            <div class=" vouch_buy">
                <a class="button exclusive" href="{$cat}" title="{l s='Buy a voucher' mod='blockvoucherz'}">{l s='Buy' mod='blockvoucherz'}</a>
            </div>
            <br/>
            <div class="vouch_slogan">
                <a href="{$cat}" title="{l s='Buy a voucher' mod='blockvoucherz'}">{l s='Lets buy vouchers!' mod='blockvoucherz'}</a>
            </div>
        {else}
            {l s='There are no gift vouchers to buy' mod='blockvoucherz'}
        {/if}
    </div>
</div>
<!-- /MODULE blockvoucherz -->