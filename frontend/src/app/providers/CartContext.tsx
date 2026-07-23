import React, { createContext, useContext, useState, useEffect, ReactNode } from 'react'

export interface CartExtra {
  id: number;
  name: string;
  price: number;
}

export interface CartItem {
  id: string; // variantId_sortedExtraIds
  product_id: number;
  product_name: string;
  variant_id: number;
  variant_name: string;
  sku: string;
  base_price: number; // original price
  promo_price?: number | null; // promotional price if active
  quantity: number;
  extras: CartExtra[];
  image_url?: string | null;
}

interface CartContextType {
  cartItems: CartItem[];
  cartCount: number;
  cartSubtotal: number;
  addToCart: (item: Omit<CartItem, 'id'>) => void;
  updateQuantity: (itemId: string, quantity: number) => void;
  removeFromCart: (itemId: string) => void;
  clearCart: () => void;
}

const CartContext = createContext<CartContextType | undefined>(undefined)

export function CartProvider({ children }: { children: ReactNode }) {
  const [cartItems, setCartItems] = useState<CartItem[]>([])

  // Load cart from LocalStorage on mount
  useEffect(() => {
    try {
      const storedCart = localStorage.getItem('dulce_encanto_cart')
      if (storedCart) {
        setCartItems(JSON.parse(storedCart))
      }
    } catch (error) {
      console.error('Failed to load cart from localStorage:', error)
    }
  }, [])

  // Save cart to LocalStorage when changed
  const saveCart = (items: CartItem[]) => {
    setCartItems(items)
    try {
      localStorage.setItem('dulce_encanto_cart', JSON.stringify(items))
    } catch (error) {
      console.error('Failed to save cart to localStorage:', error)
    }
  }

  const addToCart = (newItem: Omit<CartItem, 'id'>) => {
    const sortedExtraIds = [...newItem.extras].map(e => e.id).sort((a, b) => a - b)
    const extraKey = sortedExtraIds.join('-')
    const itemId = `${newItem.variant_id}_${extraKey}`

    const existingIndex = cartItems.findIndex(item => item.id === itemId)
    let updatedItems = [...cartItems]

    if (existingIndex > -1) {
      updatedItems[existingIndex].quantity += newItem.quantity
    } else {
      updatedItems.push({
        ...newItem,
        id: itemId
      })
    }

    saveCart(updatedItems)
  }

  const updateQuantity = (itemId: string, quantity: number) => {
    let updatedItems = cartItems.map(item => {
      if (item.id === itemId) {
        return { ...item, quantity: Math.max(0, quantity) }
      }
      return item
    }).filter(item => item.quantity > 0)

    saveCart(updatedItems)
  }

  const removeFromCart = (itemId: string) => {
    const updatedItems = cartItems.filter(item => item.id !== itemId)
    saveCart(updatedItems)
  }

  const clearCart = () => {
    saveCart([])
  }

  // Derived properties
  const cartCount = cartItems.reduce((sum, item) => sum + item.quantity, 0)
  
  const cartSubtotal = cartItems.reduce((sum, item) => {
    const activePrice = item.promo_price !== undefined && item.promo_price !== null ? item.promo_price : item.base_price
    const extrasPrice = item.extras.reduce((extraSum, extra) => extraSum + Number(extra.price), 0)
    return sum + (activePrice + extrasPrice) * item.quantity
  }, 0)

  return (
    <CartContext.Provider value={{
      cartItems,
      cartCount,
      cartSubtotal,
      addToCart,
      updateQuantity,
      removeFromCart,
      clearCart
    }}>
      {children}
    </CartContext.Provider>
  )
}

export function useCart() {
  const context = useContext(CartContext)
  if (context === undefined) {
    throw new Error('useCart must be used within a CartProvider')
  }
  return context
}
