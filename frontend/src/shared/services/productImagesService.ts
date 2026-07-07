import api from '@/lib/axios'
import { ProductImage, ApiResponse } from '@/shared/types'

const productImagesService = {
  /**
   * Get all images for a variant.
   */
  async getByVariantId(variantId: number): Promise<{ data: ApiResponse<ProductImage[]> }> {
    return api.get(`/api/v1/product-images?product_variant_id=${variantId}`)
  },

  /**
   * Upload a new image for a variant.
   */
  async upload(variantId: number, file: File, isPrimary: boolean = false): Promise<{ data: ApiResponse<ProductImage> }> {
    const formData = new FormData()
    formData.append('product_variant_id', variantId.toString())
    formData.append('image', file)
    formData.append('is_primary', isPrimary ? '1' : '0')

    return api.post('/api/v1/product-images', formData, {
      headers: {
        'Content-Type': 'multipart/form-data'
      }
    })
  },

  /**
   * Set an image as primary.
   */
  async setPrimary(id: number): Promise<{ data: ApiResponse<ProductImage> }> {
    return api.patch(`/api/v1/product-images/${id}/set-primary`)
  },

  /**
   * Delete an image.
   */
  async delete(id: number): Promise<{ data: ApiResponse<null> }> {
    return api.delete(`/api/v1/product-images/${id}`)
  }
}

export default productImagesService
