# 🍽️ Menu Management API Documentation

## Overview
The Menu Management API provides comprehensive endpoints for managing coffee shop menus, including categories, menu items, and statistics.

## 🗂️ Database Structure

### Menu Categories Table
- **id** (Primary Key)
- **name** - Category name (e.g., "Hot Coffee", "Iced Coffee")
- **description** - Category description
- **icon** - Emoji or icon representation
- **color** - Hex color code for UI
- **sort_order** - Display order
- **is_active** - Whether category is active
- **is_visible** - Whether category is visible to customers
- **available_from** - Start time (HH:mm format)
- **available_to** - End time (HH:mm format)
- **available_days** - Array of days (0=Sunday, 1=Monday, etc.)

### Menu Items Table
- **id** (Primary Key)
- **category_id** (Foreign Key to menu_categories)
- **product_id** (Optional Foreign Key to products)
- **name** - Item name
- **description** - Item description
- **price** - Base price
- **sale_price** - Sale price (optional)
- **is_on_sale** - Sale status
- **is_available** - Availability status
- **is_visible** - Visibility status
- **is_featured** - Featured status
- **sort_order** - Display order
- **tags** - JSON array of tags
- **allergens** - JSON array of allergens
- **nutrition_info** - JSON nutrition data
- **preparation_time** - Preparation time in minutes
- **customizations** - JSON customization options
- **images** - JSON array of image URLs

## 🚀 API Endpoints

### Menu Categories

#### GET `/api/menu-categories`
Retrieve all menu categories with item counts.

**Response:**
```json
{
  "status": "success",
  "data": [
    {
      "id": 1,
      "name": "Hot Coffee",
      "description": "Freshly brewed hot coffee beverages",
      "icon": "☕",
      "color": "#8B4513",
      "sort_order": 1,
      "is_active": true,
      "is_visible": true,
      "available_days": [0,1,2,3,4,5,6],
      "menu_items_count": 3
    }
  ],
  "message": "Menu categories retrieved successfully"
}
```

#### POST `/api/menu-categories`
Create a new menu category.

**Request Body:**
```json
{
  "name": "New Category",
  "description": "Category description",
  "icon": "🍕",
  "color": "#FF6B6B",
  "sort_order": 6,
  "is_active": true,
  "is_visible": true,
  "available_days": [0,1,2,3,4,5,6]
}
```

#### PUT `/api/menu-categories/{id}`
Update an existing menu category.

#### DELETE `/api/menu-categories/{id}`
Delete a menu category (also removes associated menu items).

#### PATCH `/api/menu-categories/{id}/toggle-availability`
Toggle category active status.

#### POST `/api/menu-categories/reorder`
Reorder categories.

**Request Body:**
```json
{
  "category_ids": [3, 1, 2, 4, 5]
}
```

### Menu Items

#### GET `/api/menu-items`
Retrieve menu items with optional filtering.

**Query Parameters:**
- `category_id` - Filter by category
- `available` - Filter by availability
- `featured` - Filter by featured status
- `search` - Search in name/description

#### POST `/api/menu-items`
Create a new menu item.

**Request Body:**
```json
{
  "category_id": 1,
  "name": "Espresso",
  "description": "Single shot of pure coffee",
  "price": 2.50,
  "preparation_time": 2,
  "tags": ["coffee", "hot", "espresso"],
  "allergens": []
}
```

#### PUT `/api/menu-items/{id}`
Update an existing menu item.

#### DELETE `/api/menu-items/{id}`
Delete a menu item.

#### PATCH `/api/menu-items/{id}/toggle-availability`
Toggle item availability.

#### PATCH `/api/menu-items/{id}/toggle-featured`
Toggle featured status.

#### POST `/api/menu-items/reorder`
Reorder menu items.

### Menu Statistics

#### GET `/api/menu-stats`
Get comprehensive menu statistics.

**Response:**
```json
{
  "status": "success",
  "data": {
    "total_categories": 5,
    "active_categories": 5,
    "visible_categories": 5,
    "total_items": 12,
    "active_items": 12,
    "visible_items": 12,
    "featured_items": 4,
    "on_sale_items": 0,
    "average_price": 4.75,
    "total_menu_value": 57.00,
    "categories_with_items": 5,
    "items_by_category": [
      {
        "id": 1,
        "name": "Hot Coffee",
        "icon": "☕",
        "color": "#8B4513",
        "item_count": 3,
        "is_active": true
      }
    ]
  }
}
```

#### GET `/api/menu-featured`
Get featured menu items.

#### GET `/api/menu-by-category?category_id={id}`
Get menu items by category.

#### GET `/api/menu-search?q={query}`
Search menu items.

## 🔧 Setup Instructions

### 1. Run Migrations
```bash
php artisan migrate
```

### 2. Seed Data
```bash
php artisan db:seed --class=MenuManagementSeeder
```

### 3. Verify Routes
```bash
php artisan route:list --path=api/menu
```

## 📊 Sample Data

The seeder creates:
- **5 Menu Categories**: Hot Coffee, Iced Coffee, Specialty Drinks, Pastries & Desserts, Breakfast
- **12 Menu Items**: Espresso, Cappuccino, Latte, Iced Americano, Iced Latte, Caramel Macchiato, Mocha, Croissant, Chocolate Cake, Avocado Toast, Eggs Benedict

## 🔐 Authentication

All endpoints require authentication via Laravel Sanctum. Include the Bearer token in the Authorization header:

```
Authorization: Bearer {token}
```

## 🚨 Error Handling

All endpoints return consistent error responses:

```json
{
  "status": "error",
  "message": "Error description",
  "error": "Detailed error message"
}
```

## 🔄 Frontend Integration

The API is designed to work seamlessly with the Next.js frontend. Use the provided `apiClient` methods:

```typescript
import { apiClient } from '@/lib/api/client'

// Fetch categories
const categories = await apiClient.getMenuCategories()

// Create category
const newCategory = await apiClient.createMenuCategory({
  name: 'New Category',
  description: 'Description',
  icon: '🍕',
  color: '#FF6B6B'
})
```

## 📝 Notes

- Categories are automatically ordered by `sort_order`
- Menu items are automatically ordered by category and then by `sort_order`
- Deleting a category cascades to delete associated menu items
- All timestamps are automatically managed
- JSON fields support flexible data structures for tags, allergens, etc.
