# SAP Sandbox Test-Ready Update

## What has been added

- A **SAP Sandbox - Test API Response** panel on **Super Admin > Administration > SAP S/4HANA Integration**.
- **Validate Uploaded Response (Offline)** checks the actual attached 42-row OData v2 JSON sample without contacting SAP or touching stock.
- **Test Live SAP Sandbox API** makes a read-only HTTPS GET against the exact Cost Center Activity Type collection shown in the supplied response, using the stored API key as the `APIKey` request header. It verifies the first page of records.
- Test routes require Super Admin login and CSRF tokens.
- Existing dashboard, product master, stock aging, division mapping, and controlled synchronization are unchanged.

## Deployment / testing

1. Back up your existing project and MySQL database.
2. Replace your deployed app with the ZIP's project files, retaining your deployed database credentials and secret configuration where applicable.
3. Open **Super Admin > Administration > SAP S/4HANA Integration**.
4. Click **Validate Uploaded Response (Offline)**. Expected: **42 cost-center activity types** with units H=28, PC=10, GJ=2, KG=1, MWH=1.
5. For live testing choose **SAP Sandbox API Key** under Authentication; set SAP Base URL to `https://sandbox.api.sap.com`, API Key Header to `APIKey`, enter your sandbox API key in the password-style field, and **Save Settings**. Leave **Enable Integration** unchecked and **Publish to Dashboard** unchecked.
6. Click **Test Live SAP Sandbox API**. This sends a request from the PHP server to:
   `https://sandbox.api.sap.com/s4hanacloud/sap/opu/odata/sap/API_COSTCNTRACTIVITYTYPE_CRUD_SRV/A_CostCenterActivityType?$top=50`
7. If live test fails, check outbound HTTPS from the PHP host, PHP cURL extension, key validity, API entitlement and SAP sandbox availability. The request has not been externally verified during packaging.

## Critical data mapping distinction

The uploaded JSON is **API_COSTCNTRACTIVITYTYPE_CRUD_SRV / A_CostCenterActivityType**, which describes cost center activity types.
`CostCtrActivityTypeQtyUnit` is an **activity quantity UoM**, not the product's BaseUnit and not finished-goods inventory.

There is no Product/Material, Batch, inventory quantity, manufacturing/expiry date, or warehouse stock in this sample.
**Do not map this endpoint to Product, Batch or Stock collection endpoints.** The original 3-endpoint SAP integration remains available, but actual inventory synchronization still needs those separate approved entity-set URLs with proper inventory payloads.

The sandbox API key is intentionally **not hard-coded into the distributed ZIP**. Enter it through the existing encrypted SAP credential form. Rotate the key if it has been exposed.

## Verification performed

- PHP syntax lint passed for all changed PHP files.
- Offline sample validation passed for the included 42-row JSON fixture.
- A live remote SAP request and MySQL-backed website integration were not executed in this environment.
