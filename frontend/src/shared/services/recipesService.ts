import api from '@/lib/axios'
import { Recipe, ApiResponse, ApiPaginateResponse } from '@/shared/types'

export interface RecipeInput {
  items: {
    supply_id: number;
    quantity: number;
    unit: string;
    observation?: string | null;
  }[];
}

const recipesService = {
  /**
   * Get all recipes (product variants with recipes).
   */
  async getAll(onlyActive: boolean = false): Promise<{ data: ApiResponse<Recipe[]> }> {
    const url = onlyActive 
      ? '/api/v1/recipes?paginate=false&only_active=true' 
      : '/api/v1/recipes?paginate=false'
    return api.get(url)
  },

  /**
   * Get paginated recipes.
   */
  async paginate(
    page: number = 1,
    search: string = '',
    perPage: number = 10,
    onlyActive: boolean = false
  ): Promise<{ data: ApiPaginateResponse<Recipe> }> {
    const url = onlyActive 
      ? `/api/v1/recipes?page=${page}&search=${search}&per_page=${perPage}&only_active=true`
      : `/api/v1/recipes?page=${page}&search=${search}&per_page=${perPage}`
    return api.get(url)
  },

  /**
   * Find a recipe by variant ID.
   */
  async getByVariantId(variantId: number): Promise<{ data: ApiResponse<Recipe> }> {
    return api.get(`/api/v1/recipes/${variantId}`)
  },

  /**
   * Create or update a recipe for a product variant.
   */
  async saveRecipe(variantId: number, data: RecipeInput): Promise<{ data: ApiResponse<Recipe> }> {
    return api.put(`/api/v1/recipes/${variantId}`, data)
  }
}

export default recipesService
