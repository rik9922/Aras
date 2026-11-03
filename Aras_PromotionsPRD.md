# Product Requirements Document (PRD)

# Aras Promotions

**Version:** 2.0  
**Status:** Expanded / Working PRD  
**Prepared By:** Sumit Jaiswal  
**Document Type:** Product Requirements Document

---

# 1. Document Overview

## 1.1 Purpose of This Document

This document defines the product requirements, business workflows, functional areas, integrations, user roles, and operational expectations for **Aras Promotions**.

The original PRD provided a concise high-level overview of the platform. This expanded version retains the original product concepts while providing more descriptive context around how the platform is intended to operate.

This document should serve as a shared reference for:

- Product Management
- Project Management
- Development
- QA and Testing
- Operations
- Future development and maintenance

Where a requirement is not explicitly confirmed by the existing source material, it is intentionally described at a high level rather than presenting assumptions as confirmed functionality.

---

# 2. Product Overview

## 2.1 What is Aras Promotions?

**Aras Promotions is a brand promotion and event management platform** that helps companies plan, execute, and monitor in-person promotional campaigns through Brand Ambassadors.

The platform centralizes the full promotional-event lifecycle: managing clients, brands, markets, locations, distributors, users, and events; assigning Managers and Brand Ambassadors; coordinating event execution; and tracking the operational work that follows each completed event.

Brand Ambassadors use the mobile application to view and manage assigned events, accept or decline assignments where applicable, complete check-in/check-out requirements, submit event recaps, upload photos and supporting documents, record expenses, and monitor payment status.

On the administrative side, Admins and Managers manage events and ambassador assignments, oversee event activity, review and approve recaps and expenses, and administer the workflows related to completed promotional events, reporting, invoicing, and payments.

The platform integrates with external business systems to support these workflows:

- **BEST** synchronizes event-related data and liquor usage.
- **Dwolla** supports Brand Ambassador payout processing and payment tracking.
- **QuickBooks** supports contractor/W-9 workflows, customer and invoice management, invoice generation, and accounting synchronization.
- **OneSignal** supports push notifications for assignments, reminders, and workflow updates.

Overall, Aras Promotions brings **brand promotion, event management, ambassador coordination, event reporting, billing, accounting, and ambassador payments** into a single platform. This reduces manual operational work, improves data consistency, and gives teams better visibility across the promotional event lifecycle.

The project is currently approximately **80–90% complete**. Core event management, payment, accounting, and major integration workflows are established. Current development is focused on stabilizing the latest web functionality and aligning the mobile application’s UI, functionality, and business rules with the web platform.
## 2.2 Product Concept

The core product concept can be summarized as:

**Brand / Promotional Requirement → Event → Ambassador Assignment → Event Execution → Recap → Review / Approval → Billing / Accounting → Ambassador Payment**

The exact workflow can vary based on event and business requirements, but the platform is designed around maintaining a connected record of the promotional activity from planning through completion.

---

# 3. Business Problem

Promotional campaigns can involve multiple people, events, locations, brands, distributors, managers, ambassadors, financial transactions, reports, and supporting documents.

Managing these activities through disconnected manual processes can make it difficult to:

- Maintain a single source of event information
- Coordinate Brand Ambassadors
- Track event execution
- Collect event recaps and photos
- Review expenses
- Manage approvals
- Process ambassador payments
- Synchronize accounting information
- Maintain operational visibility

Aras Promotions addresses these needs by providing a centralized platform for promotional event operations.

---

# 4. Product Objectives

The primary objectives of the platform are:

1. Simplify promotional event management.
2. Centralize event and ambassador information.
3. Improve coordination between Admins, Managers, and Brand Ambassadors.
4. Provide a structured workflow for event execution and reporting.
5. Centralize recap and expense submissions.
6. Support review and approval workflows.
7. Automate or simplify ambassador payment processing.
8. Integrate operational and financial data with external systems.
9. Provide better visibility into promotional activities.
10. Reduce manual operational processes.

---

# 5. Users and Roles

## 5.1 Admin

The Admin has broad access to the platform and is responsible for overall system and operational management.

### Responsibilities

- Manage users
- Manage clients
- Manage markets
- Create and manage promotional events
- Assign Managers
- Assign Brand Ambassadors
- Review event information
- Review submitted reports
- Manage payout-related workflows
- Configure platform settings
- Manage integrations
- Access operational and financial information
- View analytics and reports

---

## 5.2 Manager

Managers are responsible for day-to-day promotional event operations within their assigned markets or areas.

### Responsibilities

- Create promotional events
- Manage assigned markets
- Coordinate Brand Ambassadors
- Assign Brand Ambassadors to events
- Monitor event progress
- Review submitted recaps
- Review expenses
- Approve or reject submissions
- Communicate with Brand Ambassadors
- Generate or review reports

---

## 5.3 Brand Ambassador

A Brand Ambassador (BA) represents a client's brand during a promotional event.

The mobile application is the primary interface for the Brand Ambassador workflow.

### Responsibilities

- View available and assigned events
- Accept or decline event assignments
- View event details
- Check in and check out where applicable
- Perform the promotional activity
- Upload event photos
- Submit event recaps
- Submit expenses and supporting receipts
- Manage banking information
- View payment-related status

---

## 5.4 Client

A Client represents a company or brand associated with promotional campaigns.

Examples from the original PRD include:

- Coca-Cola
- Pepsi
- Monster Energy
- Red Bull

The current source material describes clients as the companies or brands sponsoring promotional campaigns. Detailed client-facing portal capabilities should be treated as future scope unless separately confirmed.

---

# 6. High-Level Business Workflow

The platform supports the following overall workflow:

```text
Client / Brand
      ↓
Promotional Campaign
      ↓
Admin / Manager
      ↓
Create Promotional Event
      ↓
Assign Brand Ambassador
      ↓
Notification
      ↓
Ambassador Accepts Event
      ↓
Event Execution
      ↓
Check In / Check Out
      ↓
Submit Recap
      ↓
Manager Review
      ↓
Approve / Reject / Resubmit
      ↓
Expense / Payment Processing
      ↓
Accounting / Invoice Processing
      ↓
Reporting
```

This represents the high-level product flow. Individual event and payment workflows may contain additional business rules.

---

# 7. Core Business Concepts

## 7.1 Client / Brand

A company or brand whose products are being promoted through promotional activities.

## 7.2 Promotional Event

A scheduled promotional activity associated with a brand/client. An event contains operational information such as scheduling, location, assignment, and event-specific requirements.

## 7.3 Brand Ambassador

A person assigned to represent the client's brand and perform the promotional activity.

## 7.4 Market

A geographic or operational grouping used to organize promotional activities and management responsibilities.

## 7.5 Event Recap

A post-event submission containing information about the completed promotional activity. Recaps may include answers to event questions, photos, expenses, and supporting information.

## 7.6 Payment

The financial process associated with compensating Brand Ambassadors after applicable event and approval requirements are satisfied.

---

# 8. Event Management

Event Management is a core module of Aras Promotions.

## 8.1 Event Creation

Admins and Managers can create promotional events.

An event may contain information such as:

- Event type
- Date
- Start and end time
- Venue / location
- Brand
- Market
- Manager
- Distributor
- Ambassador assignment
- Event-specific operational information
- Billing-related information where applicable

The exact fields may vary depending on the event type and current product implementation.

## 8.2 Event Update

Authorized users can update event information.

Updates should preserve existing event relationships and operational data unless the business workflow specifically requires the information to be changed.

## 8.3 Event Cancellation

Events may be cancelled when they are no longer expected to take place.

Cancellation should maintain the historical record of the event rather than removing operational history.

## 8.4 Event Duplication

Where supported, an existing event can be duplicated to reduce repetitive data entry for similar promotional activities.

## 8.5 Event Assignment

An event can be assigned to a Brand Ambassador.

Assignment connects the event to the ambassador's mobile workflow and may trigger notifications.

## 8.6 Event Listing

The platform provides event listings so authorized users can review and manage events.

For large datasets, event listings should use appropriate pagination, infinite scrolling, filtering, or search mechanisms rather than loading unnecessary records at once.

---

# 9. Calendar

The Calendar provides a visual representation of promotional events.

Potential calendar capabilities include:

- Monthly view
- Weekly view
- Event status
- Event filtering
- Event date and time visibility

The calendar should provide users with an operational view of scheduled promotional activities.

---

# 10. Ambassador Management

Ambassador Management covers the operational information required to work with Brand Ambassadors.

Areas include:

- Ambassador records
- Event assignments
- Availability
- Performance information
- Documents
- Certifications where applicable
- Banking information
- Payment information

The mobile application provides Brand Ambassadors with access to the parts of this information relevant to their workflow.

---

# 11. Event Assignment

The event assignment workflow connects promotional events with Brand Ambassadors.

## 11.1 Assignment

Authorized Admins or Managers can assign an ambassador to an event.

## 11.2 Ambassador Response

The Brand Ambassador can accept or decline an assignment where the workflow permits.

## 11.3 Reassignment

An event may be reassigned when the originally assigned ambassador cannot complete the event.

## 11.4 Notifications

Assignment-related notifications can inform ambassadors about newly assigned events and required actions.

---

# 12. Event Execution

Event Execution represents the actual completion of promotional activities.

Where enabled by the event workflow, the ambassador can:

1. Open the assigned event.
2. Review event details.
3. Check in.
4. Perform the promotional activity.
5. Capture required event information.
6. Upload photos or supporting information.
7. Check out.
8. Submit the event recap.

GPS verification may be used as part of event execution where supported.

---

# 13. Recap Management

Recaps provide the post-event record of promotional activity.

## 13.1 Recap Submission

After completing an event, the Brand Ambassador submits a recap.

A recap can contain:

- Event questions / answers
- Event information
- Photos
- Expenses
- Supporting documents
- Other required event information

## 13.2 Review

Managers review submitted recaps to verify that the event information is complete and acceptable.

## 13.3 Approval

A recap can be approved when the required information is satisfactory.

## 13.4 Rejection and Resubmission

If information is incomplete or requires correction, the recap may be rejected and returned to the ambassador for resubmission where supported.

---

# 14. Expense Management

The platform supports event-related expense submission and review.

## 14.1 Expense Submission

Brand Ambassadors can submit expenses associated with an event.

Expenses may include supporting receipts or documentation.

## 14.2 Expense Review

Authorized users can review submitted expenses.

## 14.3 Expense Approval

Expenses may require approval before being included in applicable payment or accounting workflows.

---

# 15. Banking

Banking functionality supports the financial onboarding required for ambassador payments.

Core capabilities include:

- Add bank account
- Verify bank account
- Set default bank account
- Maintain payment-related banking information

The banking workflow supports downstream payout processing.

---

# 16. Payments and Ambassador Payouts

Payment processing covers compensation owed to Brand Ambassadors for eligible promotional activities.

The high-level workflow is:

```text
Event Completed
      ↓
Recap Submitted
      ↓
Review / Approval
      ↓
Payment Eligibility
      ↓
Payment Processing
      ↓
Dwolla Transfer
      ↓
Payment Status
```

Actual payment eligibility and approval rules are governed by the applicable business workflow.

---

# 17. Dwolla Integration

Dwolla is used for Brand Ambassador payout processing.

### Primary Purpose

To support electronic payments and ACH transfers to Brand Ambassador bank accounts.

### Key Capabilities

- Bank account-related verification
- ACH transfers
- Payment processing
- Transfer tracking

Dwolla therefore forms part of the downstream payment workflow after applicable event and approval requirements have been satisfied.

---

# 18. QuickBooks Integration

QuickBooks is used for accounting and invoice-related workflows.

The platform supports a workflow in which a Brand Ambassador can provide a W-9 and become established as a contractor for applicable accounting processes.

### Key Areas

- W-9 onboarding
- Contractor setup
- Invoice generation
- Invoice synchronization
- Accounting information synchronization

QuickBooks provides the accounting system connection for applicable invoice and contractor workflows.

---

# 19. Billing and Invoicing

Billing and invoicing connect completed promotional activities with accounting workflows.

The platform supports invoice generation and synchronization with QuickBooks.

Where an event involves applicable billing parties, billing information can be associated with the event and used during invoice processing.

The exact billing calculation rules should be maintained as explicit business rules within the detailed implementation requirements.

---

# 20. BEST Integration

BEST integration is used to synchronize promotional event information and liquor usage associated with events.

### Primary Purpose

- Sync promotional events with BEST.
- Synchronize applicable liquor usage information related to those events.

BEST therefore provides an external operational data synchronization layer for applicable promotional events.

---

# 21. OneSignal Integration

OneSignal is used for push notifications.

Notifications may be associated with operational events such as:

- New event assignments
- Event reminders
- Approval-related actions
- Other application notifications

The exact notification triggers and recipients should follow the corresponding application workflow.

---

# 22. Corpay Integration

Corpay is a planned integration.

### Current Status

**Development has not yet started.**

The integration has been identified as a required project feature, but implementation is pending PM approval and clearance to proceed.

Development will begin once the PM confirms that the work is approved to proceed.

---

# 23. Notifications

Notifications help users stay informed about actions requiring their attention.

Notification categories include:

- Event assignments
- Event reminders
- Recap-related actions
- Approval notifications
- Payment-related notifications
- Other operational notifications

Notifications may be delivered through push notifications or email depending on the applicable workflow.

---

# 24. Dashboard

The Dashboard provides a centralized operational overview.

Potential dashboard information includes:

- Statistics
- Upcoming events
- Pending approvals
- Recent activity
- Operational summaries

The exact dashboard metrics depend on the user's role and available permissions.

---

# 25. Reports

The reporting area provides visibility into promotional activities and financial operations.

Report categories include:

- Event reports
- Ambassador reports
- Client reports
- Payment reports

Reporting should allow authorized users to understand operational activity and relevant historical information.

---

# 26. Documents

Document management supports storage and access to files associated with users and operational workflows.

Core capabilities include:

- Upload documents
- Download documents
- Update applicable documents

Documents may include supporting event, ambassador, financial, or compliance information depending on the workflow.

---

# 27. Authentication and Access Control

The platform provides authentication and role-based access.

Core authentication capabilities include:

- Login
- Forgot password
- Role-based authentication

Access to features should be controlled according to the user's role and permissions.

---

# 28. Settings

The platform includes configurable settings.

Areas identified in the original PRD include:

- General settings
- Payment settings
- Notification settings
- Integration settings

Settings should only be accessible to appropriately authorized users.

---

# 29. Mobile Application

The mobile application is primarily used by Brand Ambassadors to manage their promotional event workflow.

The application supports the ambassador's operational journey from event discovery and assignment through event execution, recap submission, and payment-related visibility.

Key mobile areas include:

- Authentication
- Event listings
- Event details
- Event assignment
- Event acceptance / decline
- Event execution
- Check-in / check-out
- Recap submission
- Photo uploads
- Expense submission
- Banking information
- Payment status
- Notifications

---

# 30. Web Application

The web application provides the primary administrative and operational interface.

Key areas include:

- Dashboard
- User management
- Client management
- Market management
- Event management
- Calendar
- Ambassador management
- Event assignment
- Recap review
- Expense review
- Payment management
- Billing and invoicing
- Reports
- Documents
- Settings
- Integrations

---

# 31. Web and Mobile Alignment

The mobile application and web application should remain aligned wherever they share the same business workflow.

The web platform should act as the primary reference for established business behavior where the same feature exists on both platforms.

Alignment areas include:

- UI/UX behavior
- Event data
- Event statuses
- Forms
- Validation rules
- Filtering
- Search
- Event details
- Assignment workflows
- Recap workflows
- Payment-related information

Recent product changes have frequently been implemented on the web platform first. The mobile application therefore requires ongoing updates to remain synchronized with the latest web functionality and UI.

The current enhancement focus is to stabilize web updates and subsequently align the mobile application with those changes.

---

# 32. Large Dataset and Performance Requirements

The platform operates with potentially large datasets, particularly for events and related operational records.

The application should avoid unnecessarily loading large datasets into a single request.

Appropriate techniques include:

- Server-side pagination
- Infinite scrolling where appropriate
- Typeahead search
- Filtered API requests
- Limited result sets
- Efficient database queries

For large reference datasets such as venues/locations, brands, markets, managers, and distributors, typeahead/search-based selection should be preferred where applicable instead of loading all records at once.

---

# 33. Business Rules

Business rules define how the system should behave beyond basic CRUD functionality.

Important business-rule categories include:

- Event status transitions
- Ambassador assignment rules
- Event acceptance
- Event completion
- Recap submission
- Recap review
- Recap rejection and resubmission
- Expense approval
- Payment eligibility
- Banking requirements
- Payout processing
- Invoice generation
- Billing calculations
- External system synchronization
- Notification triggers

Detailed rules should be maintained alongside the relevant feature requirements so that development and QA can validate behavior consistently.

---

# 34. Data and Entity Relationships

The major product concepts are connected through the following high-level relationship:

```text
Client / Brand
      ↓
Campaign / Promotional Activity
      ↓
Event
      ├── Location
      ├── Market
      ├── Manager
      ├── Distributor
      └── Brand Ambassador
              ↓
          Event Execution
              ↓
            Recap
              ↓
       Review / Approval
              ↓
     Expense / Payment
              ↓
        Billing / Invoice
              ↓
          Accounting
```

The actual database model may contain additional supporting entities and relationships.

---

# 35. Technology Stack

| Component | Technology |
|------------|------------|
| Backend | Laravel |
| Frontend | Laravel Blade |
| Mobile | Ionic + Angular |
| Database | MySQL |
| Payment | Dwolla |
| Accounting | QuickBooks |
| Push Notifications | OneSignal |
| External Integration | BEST |

---

# 36. Integration Overview

| Integration | Primary Purpose |
|------------|-----------------|
| Dwolla | Brand Ambassador payouts and ACH transfers |
| QuickBooks | W-9/contractor, invoice and accounting synchronization |
| OneSignal | Push notifications |
| BEST | Event and liquor usage synchronization |
| Corpay | Planned future integration |

---

# 37. Non-Functional Requirements

## 37.1 Performance

The system should provide responsive user interactions while handling large event and reference datasets.

## 37.2 Scalability

The architecture should support increasing numbers of:

- Events
- Brand Ambassadors
- Clients
- Locations
- Markets
- Transactions

## 37.3 Security

User access should be controlled through authentication and role-based authorization.

Sensitive financial and personal information should only be accessible to authorized users.

## 37.4 Reliability

External integrations should be handled in a way that allows failures to be identified and operational workflows to be monitored.

## 37.5 Mobile Usability

The mobile application should provide an efficient experience for Brand Ambassadors performing event-related tasks in real-world environments.

---

# 38. Error Handling and Operational Considerations

The system should provide appropriate handling for:

- Invalid input
- Missing required information
- Failed external integrations
- Failed payment processing
- Invalid or incomplete recaps
- Upload failures
- Authentication failures
- Network failures
- Duplicate or conflicting operations

Where an external integration fails, the application should preserve enough information to identify the failed operation and allow appropriate operational follow-up.

---

# 39. Current Project Status

The project is currently estimated to be approximately **80–90% complete**.

### Completed / Established Areas

- Event Management
- BEST Integration
- Dwolla Integration for Brand Ambassador payouts
- QuickBooks integration
- W-9 / contractor workflow
- Invoice generation and synchronization

### Pending

- Corpay Integration

### Current Blockage

No major technical blockage is currently identified.

### Current Development Focus

The immediate development focus is:

- Stabilizing the latest web updates.
- Improving the mobile application UI.
- Aligning mobile functionality with the web platform.
- Resolving functional differences between web and mobile.
- Ensuring consistent business rules and validation across platforms.

---

# 40. Upcoming Implementation and Enhancements

The next major phase focuses on bringing the mobile application in sync with the web platform.

Key areas include:

- UI/UX alignment
- Event management alignment
- Form and validation alignment
- Event listing and filtering improvements
- Consistent statuses and workflows
- Large dataset optimization
- Typeahead/search improvements
- Functional consistency between web and mobile
- Continued stabilization of web functionality before mobile implementation

---

# 41. Future Scope

The original PRD identifies the following future possibilities:

- Client Portal
- Analytics Dashboard
- AI-based Ambassador Recommendation
- GPS Live Tracking
- Digital Contracts
- SMS Notifications
- Performance Scorecards
- Campaign Analytics

These items should be treated as future scope unless separately approved and added to the active implementation roadmap.

---

# 42. Acceptance Criteria — General

A feature should generally be considered complete when:

1. The required user workflow is implemented.
2. Appropriate roles can access the feature.
3. Required validation is implemented.
4. Business rules are correctly enforced.
5. Data is persisted correctly.
6. Related workflows continue to function.
7. Relevant integrations work as expected.
8. Error conditions are handled appropriately.
9. Web and mobile behavior is aligned where applicable.
10. QA can verify the expected behavior using defined acceptance criteria.

Feature-specific acceptance criteria should be documented when individual requirements are implemented.

---

# 43. Product Success Indicators

The platform should ultimately improve:

- Promotional event operational efficiency
- Ambassador coordination
- Event visibility
- Recap completion and review
- Expense management
- Payment processing
- Accounting synchronization
- Data consistency
- Administrative visibility
- Overall reduction in manual operational work

---

# 44. Glossary

| Term | Meaning |
|------|---------|
| BA | Brand Ambassador |
| Client | Company or brand sponsoring promotional activities |
| Event | Scheduled promotional activity |
| Recap | Post-event report submitted by the Brand Ambassador |
| Market | Geographic or operational grouping |
| Payout | Payment made to a Brand Ambassador |
| BEST | External integration used for event and liquor usage synchronization |
| Dwolla | Payment service used for applicable BA payouts |
| QuickBooks | Accounting platform used for applicable contractor and invoice workflows |
| Corpay | Planned external integration |

---

# 45. Document Maintenance

This PRD should be updated when there are significant changes to:

- Product functionality
- Business rules
- User workflows
- Integrations
- Roles and permissions
- Payment processes
- Web/mobile behavior
- Future roadmap

The PRD should remain aligned with approved product requirements and should not be treated as a replacement for implementation-specific technical documentation.

---

# 46. Summary

Aras Promotions is a centralized promotional event management platform designed to connect brands, promotional events, Managers, and Brand Ambassadors through a structured operational workflow.

The platform covers the lifecycle from event creation and ambassador assignment through event execution, recap submission, review, billing, accounting, and Brand Ambassador payouts.

The core operational and integration capabilities are substantially implemented. The current priority is to stabilize the web platform and progressively bring the mobile application into functional and UI alignment with the latest web implementation.

Corpay remains a planned integration that has not yet entered development and is awaiting PM approval and clearance to proceed.
