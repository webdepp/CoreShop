# CoreShop Product Filter

After creating the index, you can configure the Filters.

Todo that, you need to open "Product" > "Filters".

## Create a new Filter

You can create different Filters for different Categories.

A filter exists of different settings, pre-conditions, filters and similar products.

### Filter Settings

![Filter Settings](img/filter-settings.png)

| Field            | Description |
| ---------------- |-------------|
| Name             | Name of the Filter-Set |
| Index            | Which Index should be used |
| Order            | Order of the products |
| Order Key        | Order key (index-field) to sort from |
| Results per Page | How many products should be displayed per page, you can use Shop Settings, or override it |

### Pre-Conditions

![Filter Pre-Conditions](img/filter-preconditions.png)

You can define pre-filters for the index.

### Conditions

![Filter Conditions](img/filter-conditions.png)

Here you can define different kind of filters. These filters will be displayed on the front-page for customers to find products.

CoreShop currently supports 4 types of filters:

 - **Select**
 - **Multiselect**
 - **Range**
 - **Boolean**

#### Select Condition

A select condition is basically just a simple dropdown field where customer can select one field.

![Filter Condition Select](img/filter-condition-select.png)

#### Multiselect Condition

A multi-select condition is basically a list of fields where customer can select multiple entries.

![Filter Condition Select](img/filter-condition-multiselect.png)

#### Range Condition

A Range Condition is a slider of two ranges. The ranges are calculated automatically using MIN and MAX values.

![Filter Condition Select](img/filter-condition-range.png)

#### Boolean Condition

Boolean is a Condition where the customer can check different values.

![Filter Condition Select](img/filter-condition-boolean.png)

### Similarity

Similarity works in CoreShop using a **number**. The number is then sorted and the result is limited. So, for example you could use the [soundex](http://php.net/manual/de/function.soundex.php) function to calculate the similarity index.

![Filter Similarity](img/filter-similarity.png)

## Filter -> Category

To assign a filter to a category, open a CoreShopCategory object and select one of your filters.

![Filter Condition Select](img/filter-category.png)