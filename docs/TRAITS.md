# Traits Reference

The package includes several reusable traits for your modules.

## Required Traits

- **ApiResponser**  
  Uniform API responses for your controllers.

- **SoftDeletes**  
  Laravel's soft delete functionality for models.

## Optional Traits

- **ActivityLogHelper**  
  Easily track changes and activity logs on models.

- **PdfGeneratorTrait**  
  Generate PDFs from Blade views and save them with ease.

- **SmsSender**  
  Send SMS via Twilio with logging and UK number formatting.

- **UserUpdater**  
  Automatically manage `created_by`, `updated_by`, and `deleted_by` fields.

## How Traits Are Published

- When you generate your first module, all traits from the package's `stubs/traits/` directory are copied into your app at `app/Core/Traits/`.
- If a trait already exists, it will not be overwritten.
- You are free to customize these traits in your app — future generations will not overwrite them.

## Adding Your Own Traits

- Simply add your new trait stub to `stubs/traits/`, and it will be published on the next module generation.

---

See trait source code for usage examples and customization points.
