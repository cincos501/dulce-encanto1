export interface User {
  id: number;
  full_name: string;
  email: string;
  phone?: string | null;
  is_active: boolean;
  roles?: string[];
  permissions?: string[];
  created_at?: string;
  updated_at?: string;
}

export interface Category {
  id: number;
  name: string;
  description?: string | null;
  is_active: boolean;
  created_at?: string;
  updated_at?: string;
}

export interface Product {
  id: number;
  name: string;
  description?: string | null;
  category_id: number;
  is_active: boolean;
  category?: Category;
  variants?: ProductVariant[];
  promotions?: Promotion[];
  created_at?: string;
  updated_at?: string;
}

export interface ProductVariant {
  id: number;
  product_id: number;
  name: string;
  sku: string;
  base_price: number;
  is_active: boolean;
  product?: Product;
  images?: ProductImage[];
  extras?: Extra[];
  created_at?: string;
  updated_at?: string;
}

export interface ProductImage {
  id: number;
  product_variant_id: number;
  image_url: string;
  is_primary: boolean;
  created_at?: string;
  updated_at?: string;
}

export interface Promotion {
  id: number;
  name: string;
  description?: string | null;
  discount_type: 'percentage' | 'fixed';
  discount: number;
  start_date: string;
  end_date: string;
  is_active: boolean;
  products?: Product[];
  created_at?: string;
  updated_at?: string;
}

export interface Extra {
  id: number;
  name: string;
  price: number;
  description?: string | null;
  is_active: boolean;
  created_at?: string;
  updated_at?: string;
}

export interface Pagination {
  current_page: number;
  last_page: number;
  per_page: number;
  total: number;
}

export interface ApiResponse<T> {
  success: boolean;
  message: string;
  data: T;
}

export interface ApiPaginateResponse<T> {
  success: boolean;
  message: string;
  data: T[];
  meta: Pagination;
}

export interface CatalogItem {
  id: number;
  name: string;
  description: string | null;
  category: string;
  min_price: number;
  image: string | null;
}

export interface CatalogVariant {
  id: number;
  name: string;
  sku: string;
  price: number;
}

export interface CatalogExtra {
  id: number;
  name: string;
  description: string | null;
  price: number;
}

export interface CatalogPromotion {
  id: number;
  name: string;
  description: string | null;
  discount_type: 'percentage' | 'fixed';
  discount: number;
}

export interface CatalogDetail {
  id: number;
  name: string;
  description: string | null;
  category: string;
  gallery: { id: number; image_url: string; is_primary: boolean }[];
  variants: CatalogVariant[];
  extras: CatalogExtra[];
  promotions: CatalogPromotion[];
}
