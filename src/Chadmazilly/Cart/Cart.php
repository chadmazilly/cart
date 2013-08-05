<?php namespace Chadmazilly\Cart;

use Session;
use Chadmazilly\Cart\Exceptions\CartException;

class Cart
{
	/**
	 * shoppingcart
	 *   - items
	 *       - id
	 *       - price
	 *       - qty
	 *       - meta
	 *       -- (name)
	 *       -- (uri)
	 *       -- (value)
	 *       -- (req_shipping)
	 *       -- (freight_charge)
	 *   - rowcount
	 *   - itemcount
	 *   - subtotal
	 */
	
	/**
	 * Creates shopping cart session
	 */
	public function initialize()
	{
		Session::put('shoppingcart', array(
			'items'     => null,
			'rowcount'  => 0,
			'itemcount' => 0,
			'subtotal'  => 0
		));
	}

	/**
	 * Gets the current value for specified attribute (or all if none specified)
	 * 
	 * @param  string  $attribute  (null)
	 * @return null|string|array
	 */
	public function get($attribute = null)
	{
		if ($attribute)
		{
			$key = '.'.$attribute;
		}
		else
		{
			$key = '';
		}

		return Session::get('shoppingcart'.$key, 0);
	}

	/**
	 * Replaces the current value of the specified attribute with value given
	 * 
	 * @param  string  $attribute  (null)
	 * @param  string  $value      (null)
	 */
	public function replace($attribute = null, $value = null)
	{
		if (!$attribute)
		{
			throw new CartException;
		}

		Session::forget('shoppingcart.'.$attribute);
		Session::put('shoppingcart.'.$attribute, $value);
	}

	/**
	 * Increment the current value of the specified attribute by the value given
	 * 
	 * @param  string  $attribute  (null)
	 * @param  string  $value      (null)
	 */
	public function update($attribute = null, $value = null)
	{
		if (!$attribute)
		{
			throw new CartException;
		}

		$current_value = Session::get('shoppingcart.'.$attribute);
		$this->replace($attribute, $current_value + $value);
	}

	/**
	 * Returns a boolean indicating if cart exists (optional: with items)
	 * 
	 * @param  bool  $withItems  (false)
	 * @return bool
	 */
	public function exists($withItems = false)
	{
		if (Session::has('shoppingcart', 0))
		{
			if ($withItems && !Session::get('shoppingcart.rowcount', 0))
			{
				return false;
			}

			return true;
		}

		return false;
	}

	/**
	 * Adds an item to the cart
	 * 
	 * @param  int    $item_id
	 * @param  int    $price
	 * @param  int    $qty
	 * @param  array  $meta
	 */
	public function addItem($item_id, $price, $qty, $meta=array())
	{
		# get current cart items
		$cartitems = $this->get('items');

		# render cart_id
		$cart_id = 'opt'.$item_id;

		# if item id does not exist in cart, add it
		if (!isset($cartitems[$cart_id]))
		{
			# if no cart, initialize it
			if (!Session::has('shoppingcart'))
			{
				$this->initialize();
			}

			# insert new shoppingcart item
			$cartitems = $this->get('items');

			$cartitems[$cart_id] = array(
				'id'             => $item_id,
				'price'          => $price,
				'qty'            => $qty,
				// meta data below
				'name'           => $meta['name'],
				'uri'            => $meta['uri'],
				'value'          => $meta['value'],
				'req_shipping'   => $meta['req_shipping'],
				'freight_charge' => $meta['freight_charge']
			);

			$this->replace('items', $cartitems);
			$this->update('rowcount', 1);
			$this->update('itemcount', $qty);
			$this->update('subtotal', ($qty * $price));
		}
		# if item already exits in the cart, increment the qty
		else
		{
			# update cart item
			$updateitem = $cartitems[$cart_id];
			unset($cartitems[$cart_id]);

			$cartitems[$cart_id] = array(
				'id'             => $updateitem['id'],
				'price'          => $updateitem['price'],
				'qty'            => $updateitem['qty']+$qty,
				// meta data below
				'name'           => $updateitem['name'],
				'uri'            => $updateitem['uri'],
				'value'          => $updateitem['value'],
				'req_shipping'   => $updateitem['req_shipping'],
				'freight_charge' => $updateitem['freight_charge']
			);

			$this->replace('items', $cartitems);
			$this->update('itemcount', $qty);
			$this->update('subtotal', ($qty * $updateitem['price']));
		}

	}

	/**
	 * Removes an item from the cart
	 * 
	 * @param  int  $cart_id
	 */
	public function removeRow($cart_id)
	{
		$cartitems = $this->get('items');

		$this->update('rowcount', -1);
		$this->update('itemcount', -$cartitems[$cart_id]['qty']);
		$this->update('subtotal', -($cartitems[$cart_id]['qty'] * $cartitems[$cart_id]['price']));

		# remove this cart_id from array
		unset($cartitems[$cart_id]);
		$this->replace('items', $cartitems);
	}

	/**
	 * Returns boolean indicating whether any item on order needs to be shipping
	 * 
	 */
	public function requiresShipping()
	{
		$cartitems = $this->get('items');

		foreach ($cartitems as $cartitem)
		{
			if ($cartitem['req_shipping'])
			{
				return true;
			}
		}

		return false;
	}

	/**
	 * Destroys the checkout session
	 * 
	 */
	public function kill()
	{
		Session::forget('shoppingcart');
	}
}