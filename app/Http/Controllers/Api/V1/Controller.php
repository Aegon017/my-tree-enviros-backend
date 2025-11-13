<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller as BaseController;

/**
 * @OA\Info(
 *     title="MyTree Enviros API",
 *     version="1.0.0",
 *     description="API documentation for MyTree Enviros - Tree Sponsorship, Adoption & E-commerce Platform",
 *
 *     @OA\Contact(
 *         email="support@mytreeenviros.com",
 *         name="API Support"
 *     ),
 *
 *     @OA\License(
 *         name="Proprietary",
 *         url="https://mytreeenviros.com/license"
 *     )
 * )
 *
 * @OA\Server(
 *     url="http://localhost:8000",
 *     description="Local Development Server"
 * )
 * @OA\Server(
 *     url="https://api.mytree.care",
 *     description="Production Server"
 * )
 *
 * @OA\SecurityScheme(
 *     securityScheme="bearerAuth",
 *     type="http",
 *     scheme="bearer",
 *     bearerFormat="JWT",
 *     description="Enter your bearer token in the format: Bearer {token}"
 * )
 *
 * @OA\Schema(
 *     schema="Product",
 *     type="object",
 *     title="Product",
 *     description="E-commerce Product model",
 *
 *     @OA\Property(property="id", type="integer", example=1),
 *     @OA\Property(property="name", type="string", example="Organic Fertilizer"),
 *     @OA\Property(property="slug", type="string", example="organic-fertilizer"),
 *     @OA\Property(property="botanical_name", type="string", example="Organic Mix", nullable=true),
 *     @OA\Property(property="nick_name", type="string", example="Best Fertilizer", nullable=true),
 *     @OA\Property(property="short_description", type="string", example="High quality organic fertilizer", nullable=true),
 *     @OA\Property(property="description", type="string", example="Detailed product description here", nullable=true),
 *     @OA\Property(property="is_active", type="boolean", example=true),
 *     @OA\Property(
 *         property="category",
 *         type="object",
 *         @OA\Property(property="id", type="integer", example=1),
 *         @OA\Property(property="name", type="string", example="Fertilizers"),
 *         @OA\Property(property="slug", type="string", example="fertilizers")
 *     ),
 *     @OA\Property(
 *         property="images",
 *         type="array",
 *
 *         @OA\Items(
 *             type="object",
 *
 *             @OA\Property(property="id", type="integer", example=1),
 *             @OA\Property(property="url", type="string", example="https://example.com/images/product.jpg"),
 *             @OA\Property(property="thumb", type="string", example="https://example.com/images/product-thumb.jpg")
 *         )
 *     ),
 *     @OA\Property(
 *         property="inventory",
 *         type="object",
 *         @OA\Property(property="id", type="integer", example=1),
 *         @OA\Property(property="stock_quantity", type="integer", example=100),
 *         @OA\Property(property="is_instock", type="boolean", example=true),
 *         @OA\Property(property="has_variants", type="boolean", example=true)
 *     ),
 *     @OA\Property(property="price", type="number", format="float", example=499.99, nullable=true),
 *     @OA\Property(property="formatted_price", type="string", example="₹499.99", nullable=true),
 *     @OA\Property(property="in_wishlist", type="boolean", example=false),
 *     @OA\Property(property="created_at", type="string", format="date-time", example="2025-01-01T00:00:00.000000Z"),
 *     @OA\Property(property="updated_at", type="string", format="date-time", example="2025-01-01T00:00:00.000000Z")
 * )
 *
 * @OA\Schema(
 *     schema="ProductVariant",
 *     type="object",
 *     title="Product Variant",
 *     description="Product variant with color/size options",
 *
 *     @OA\Property(property="id", type="integer", example=1),
 *     @OA\Property(property="sku", type="string", example="FERT-001-RED-5KG"),
 *     @OA\Property(property="color", type="string", example="Red", nullable=true),
 *     @OA\Property(property="size", type="string", example="5kg", nullable=true),
 *     @OA\Property(property="variant_name", type="string", example="Red 5kg"),
 *     @OA\Property(property="inventory_id", type="integer", example=1),
 *     @OA\Property(property="stock_quantity", type="integer", example=50),
 *     @OA\Property(property="is_instock", type="boolean", example=true),
 *     @OA\Property(property="price", type="number", format="float", example=499.99, nullable=true),
 *     @OA\Property(property="formatted_price", type="string", example="₹499.99", nullable=true),
 *     @OA\Property(property="in_wishlist", type="boolean", example=false),
 *     @OA\Property(property="created_at", type="string", format="date-time"),
 *     @OA\Property(property="updated_at", type="string", format="date-time")
 * )
 *
 * @OA\Schema(
 *     schema="Wishlist",
 *     type="object",
 *     title="Wishlist",
 *     description="User's wishlist containing products",
 *
 *     @OA\Property(property="id", type="integer", example=1),
 *     @OA\Property(property="user_id", type="integer", example=1),
 *     @OA\Property(
 *         property="items",
 *         type="array",
 *
 *         @OA\Items(ref="#/components/schemas/WishlistItem")
 *     ),
 *
 *     @OA\Property(property="total_items", type="integer", example=5),
 *     @OA\Property(property="created_at", type="string", format="date-time"),
 *     @OA\Property(property="updated_at", type="string", format="date-time")
 * )
 *
 * @OA\Schema(
 *     schema="WishlistItem",
 *     type="object",
 *     title="Wishlist Item",
 *     description="Item in user's wishlist",
 *
 *     @OA\Property(property="id", type="integer", example=1),
 *     @OA\Property(property="wishlist_id", type="integer", example=1),
 *     @OA\Property(property="product_id", type="integer", example=1),
 *     @OA\Property(property="product_variant_id", type="integer", example=1, nullable=true),
 *     @OA\Property(property="is_variant", type="boolean", example=false),
 *     @OA\Property(property="product_name", type="string", example="Organic Fertilizer"),
 *     @OA\Property(property="product_image", type="string", example="https://example.com/images/product.jpg", nullable=true),
 *     @OA\Property(
 *         property="stock",
 *         type="object",
 *         @OA\Property(property="is_instock", type="boolean", example=true),
 *         @OA\Property(property="quantity", type="integer", example=100)
 *     ),
 *     @OA\Property(property="created_at", type="string", format="date-time"),
 *     @OA\Property(property="updated_at", type="string", format="date-time")
 * )
 *
 * @OA\Schema(
 *     schema="Cart",
 *     type="object",
 *     title="Shopping Cart",
 *     description="User's shopping cart",
 *
 *     @OA\Property(property="id", type="integer", example=1),
 *     @OA\Property(property="user_id", type="integer", example=1),
 *     @OA\Property(
 *         property="items",
 *         type="array",
 *
 *         @OA\Items(ref="#/components/schemas/CartItem")
 *     ),
 *
 *     @OA\Property(property="total_items", type="integer", example=3),
 *     @OA\Property(property="total_amount", type="number", format="float", example=2500.00),
 *     @OA\Property(property="formatted_total", type="string", example="₹2,500.00"),
 *     @OA\Property(property="is_expired", type="boolean", example=false),
 *     @OA\Property(property="created_at", type="string", format="date-time"),
 *     @OA\Property(property="updated_at", type="string", format="date-time")
 * )
 *
 * @OA\Schema(
 *     schema="CartItem",
 *     type="object",
 *     title="Cart Item",
 *     description="Item in shopping cart",
 *
 *     @OA\Property(property="id", type="integer", example=1),
 *     @OA\Property(property="cart_id", type="integer", example=1),
 *     @OA\Property(property="item_type", type="string", enum={"tree", "product", "campaign"}, example="product"),
 *     @OA\Property(property="quantity", type="integer", example=2),
 *     @OA\Property(property="price", type="number", format="float", example=500.00),
 *     @OA\Property(property="formatted_price", type="string", example="₹500.00"),
 *     @OA\Property(property="subtotal", type="number", format="float", example=1000.00),
 *     @OA\Property(property="formatted_subtotal", type="string", example="₹1,000.00"),
 *     @OA\Property(
 *         property="item",
 *         type="object",
 *         @OA\Property(property="type", type="string", example="product"),
 *         @OA\Property(property="name", type="string", example="Organic Fertilizer"),
 *         @OA\Property(property="sku", type="string", example="FERT-001"),
 *         @OA\Property(property="image", type="string", example="https://example.com/images/product.jpg")
 *     ),
 *     @OA\Property(property="created_at", type="string", format="date-time"),
 *     @OA\Property(property="updated_at", type="string", format="date-time")
 * )
 *
 * @OA\Schema(
 *     schema="Error",
 *     type="object",
 *     title="Error Response",
 *
 *     @OA\Property(property="success", type="boolean", example=false),
 *     @OA\Property(property="message", type="string", example="Error message"),
 *     @OA\Property(
 *         property="errors",
 *         type="object",
 *         additionalProperties=true,
 *         example={"field": {"Validation error message"}}
 *     )
 * )
 *
 * @OA\Schema(
 *     schema="Success",
 *     type="object",
 *     title="Success Response",
 *
 *     @OA\Property(property="success", type="boolean", example=true),
 *     @OA\Property(property="message", type="string", example="Success"),
 *     @OA\Property(property="data", type="object", additionalProperties=true)
 * )
 *
 * @OA\Schema(
 *     schema="Order",
 *     type="object",
 *     title="Order",
 *     description="Order model for trees, products, and campaigns",
 *
 *     @OA\Property(property="id", type="integer", example=1),
 *     @OA\Property(property="order_number", type="string", example="ORD-ABC-20250101-1234"),
 *     @OA\Property(property="user_id", type="integer", example=1),
 *     @OA\Property(property="type", type="string", enum={"sponsor", "adopt", "product", "campaign"}, example="sponsor"),
 *     @OA\Property(property="type_label", type="string", example="Sponsor"),
 *     @OA\Property(property="status", type="string", enum={"pending", "paid", "failed", "success", "cancelled", "refunded", "completed"}, example="pending"),
 *     @OA\Property(property="status_label", type="string", example="Pending"),
 *     @OA\Property(property="total_amount", type="string", example="590.00"),
 *     @OA\Property(property="discount_amount", type="string", example="0.00"),
 *     @OA\Property(property="gst_amount", type="string", example="90.00"),
 *     @OA\Property(property="cgst_amount", type="string", example="45.00"),
 *     @OA\Property(property="sgst_amount", type="string", example="45.00"),
 *     @OA\Property(property="formatted_total", type="string", example="₹590.00"),
 *     @OA\Property(property="currency", type="string", example="INR"),
 *     @OA\Property(property="created_at", type="string", format="date-time"),
 *     @OA\Property(property="updated_at", type="string", format="date-time")
 * )
 *
 * @OA\Schema(
 *     schema="OrderItem",
 *     type="object",
 *     title="Order Item",
 *     description="Item in an order",
 *
 *     @OA\Property(property="id", type="integer", example=1),
 *     @OA\Property(property="order_id", type="integer", example=1),
 *     @OA\Property(property="item_type", type="string", enum={"tree", "product", "campaign"}, example="tree"),
 *     @OA\Property(property="quantity", type="integer", example=1),
 *     @OA\Property(property="price", type="string", example="500.00"),
 *     @OA\Property(property="formatted_price", type="string", example="₹500.00"),
 *     @OA\Property(property="discount_amount", type="string", example="0.00"),
 *     @OA\Property(property="gst_amount", type="string", example="90.00"),
 *     @OA\Property(property="subtotal", type="number", format="float", example=500.00),
 *     @OA\Property(property="total", type="number", format="float", example=590.00),
 *     @OA\Property(property="start_date", type="string", format="date-time", nullable=true),
 *     @OA\Property(property="end_date", type="string", format="date-time", nullable=true),
 *     @OA\Property(property="is_renewal", type="boolean", example=false),
 *     @OA\Property(property="created_at", type="string", format="date-time"),
 *     @OA\Property(property="updated_at", type="string", format="date-time")
 * )
 *
 * @OA\Schema(
 *     schema="Tree",
 *     type="object",
 *     title="Tree",
 *     description="Tree model for sponsorship and adoption",
 *
 *     @OA\Property(property="id", type="integer", example=1),
 *     @OA\Property(property="sku", type="string", example="TREE-0001"),
 *     @OA\Property(property="name", type="string", example="Oak Tree"),
 *     @OA\Property(property="slug", type="string", example="oak-tree"),
 *     @OA\Property(property="age", type="integer", example=5),
 *     @OA\Property(property="age_unit", type="string", enum={"day", "month", "year"}, example="year"),
 *     @OA\Property(property="age_display", type="string", example="5 Year"),
 *     @OA\Property(property="description", type="string", example="A beautiful oak tree"),
 *     @OA\Property(property="is_active", type="boolean", example=true),
 *     @OA\Property(property="thumbnail", type="string", example="https://example.com/thumbnails/oak.jpg"),
 *     @OA\Property(property="available_instances_count", type="integer", example=10),
 *     @OA\Property(property="created_at", type="string", format="date-time"),
 *     @OA\Property(property="updated_at", type="string", format="date-time")
 * )
 *
 * @OA\Schema(
 *     schema="TreePlanPrice",
 *     type="object",
 *     title="Tree Plan Price",
 *     description="Pricing for tree sponsorship/adoption plans",
 *
 *     @OA\Property(property="id", type="integer", example=1),
 *     @OA\Property(property="sku", type="string", example="TREE-0001-SPO-0001-0001"),
 *     @OA\Property(property="price", type="string", example="500.00"),
 *     @OA\Property(property="formatted_price", type="string", example="₹500.00"),
 *     @OA\Property(property="is_active", type="boolean", example=true),
 *     @OA\Property(property="tree_id", type="integer", example=1),
 *     @OA\Property(property="plan_id", type="integer", example=1),
 *     @OA\Property(property="created_at", type="string", format="date-time"),
 *     @OA\Property(property="updated_at", type="string", format="date-time")
 * )
 *
 * @OA\Schema(
 *     schema="User",
 *     type="object",
 *     title="User",
 *     description="User model",
 *
 *     @OA\Property(property="id", type="integer", example=1),
 *     @OA\Property(property="type", type="string", enum={"individual", "organization"}, example="individual"),
 *     @OA\Property(property="name", type="string", example="John Doe"),
 *     @OA\Property(property="email", type="string", format="email", example="john@example.com", nullable=true),
 *     @OA\Property(property="country_code", type="string", example="+91"),
 *     @OA\Property(property="phone", type="string", example="9876543210"),
 *     @OA\Property(property="avatar_url", type="string", example="https://example.com/avatars/user.jpg", nullable=true),
 *     @OA\Property(property="created_at", type="string", format="date-time"),
 *     @OA\Property(property="updated_at", type="string", format="date-time")
 * )
 */
final class Controller extends BaseController
{
    //
}
