# C2C_web_platform
[For studies]

A fully fledged e-commnerse platform that facilitates customer-to-customer transactions.
Implements a buyer-to-seller messaging system that allows buyers to communicate with sellers on a product or order, inspired by platforms such as Aliexpress.

---

### Buyers
Buyers are able to go through the buying process:
- Adding items to a DB persistent cart
- Checking out to place an oder
- Picking from shipping options
- Make a demo-payment
- Track their oders and view history.

Users are able to register as regular buyers and upgrade to become sellers at anytime.

### Sellers
Sellers are able to perform the same actions as buyers, while also being able to list their own products and manage/forfill orders to other buyers.

The entire process or managing products and their variations can be done through the platform by a seller.
This allows the sellers to create, delete and modify all their listings.

---

## Some little details

The page used for creating new products is reused and auto-polulated when modifying a product.

When a buyer uses the "Contact Seller" button on a product, a new chat is auto-created and the user's text-input is autopopulated with the Product's details, making it clear what product they are contacting the seller regarding.
