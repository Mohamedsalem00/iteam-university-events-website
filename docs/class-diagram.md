# iTeam University - Class Diagram

The following diagram illustrates the class structure and relationships between entities in the iTeam University Event Management System.

```mermaid
classDiagram
    class User {
        +Int user_id
        +String first_name
        +String last_name
        +String email
        +String password
        +String profile_picture
        +DateTime registration_date
        +Enum statusclassDiagram
    class User {
        +Int user_id
        +String first_name
        +String last_name
        +String email
        +String password
        +String profile_picture
        +DateTime registration_date
        +Enum status

        +register()
        +login()
        +updateProfile()
        +deleteAccount()
        +viewNotifications()
        +registerForEvent()
    }

    class Organization {
        +Int organization_id
        +String name
        +String email
        +String password
        +String description
        +String profile_picture
        +DateTime registration_date
        +Enum status

        +register()
        +login()
        +createEvent()
        +updateEvent()
        +deleteEvent()
        +postJobOffer()
        +updateProfile()
    }

    class Admin {
        +Int admin_id
        +String username
        +String email
        +String password
        +DateTime created_at

        +login()
        +manageUsers()
        +manageOrganizations()
        +manageEvents()
    }

    class Event {
        +Int event_id
        +String title
        +String description
        +DateTime start_date
        +DateTime end_date
        +String location
        +Enum event_type
        +Int max_capacity
        +Int organizer_id

        +createEvent()
        +updateEvent()
        +deleteEvent()
        +viewEventDetails()
    }

    class EventRegistration {
        +Int registration_id
        +Int event_id
        +Int user_id
        +DateTime registration_date
        +Enum status

        +registerUser()
        +cancelRegistration()
        +confirmRegistration()
    }

    class EventGallery {
        +Int image_id
        +Int event_id
        +String image_url
        +DateTime upload_date
        +String caption

        +addImage()
        +deleteImage()
        +viewGallery()
    }

    class Notification {
        +Int notification_id
        +Int user_id
        +Int event_id
        +Enum notification_type
        +String message
        +DateTime send_date

        +sendNotification()
        +viewNotification()
        +deleteNotification()
    }

    class JobOffer {
        +Int job_offer_id
        +Int organization_id
        +String title
        +String description
        +DateTime posted_date

        +createJobOffer()
        +updateJobOffer()
        +deleteJobOffer()
        +viewJobOffer()
    }

    Organization "1" --> "0..*" Event : organizes
    User "1" --> "0..*" EventRegistration : registers
    User "1" --> "0..*" Notification : receives
    Event "1" --> "0..*" EventRegistration : has
    Event "1" --> "0..*" EventGallery : contains
    Event "1" --> "0..*" Notification : related to
    Organization "1" --> "0..*" JobOffer : posts
Diagramme de Classe

        +register()
        +login()
        +updateProfile()
        +deleteAccount()
        +viewNotifications()
        +registerForEvent()
    }

    class Organization {
        +Int organization_id
        +String name
        +String email
        +String password
        +String description
        +String profile_picture
        +DateTime registration_date
        +Enum status

        +register()
        +login()
        +createEvent()
        +updateEvent()
        +deleteEvent()
        +postJobOffer()
        +updateProfile()
    }

    class Admin {
        +Int admin_id
        +String username
        +String email
        +String password
        +DateTime created_at

        +login()
        +manageUsers()
        +manageOrganizations()
        +manageEvents()
    }

    class Event {
        +Int event_id
        +String title
        +String description
        +DateTime start_date
        +DateTime end_date
        +String location
        +Enum event_type
        +Int max_capacity
        +Int organizer_id

        +createEvent()
        +updateEvent()
        +deleteEvent()
        +viewEventDetails()
    }

    class EventRegistration {
        +Int registration_id
        +Int event_id
        +Int user_id
        +DateTime registration_date
        +Enum status

        +registerUser()
        +cancelRegistration()
        +confirmRegistration()
    }

    class EventGallery {
        +Int image_id
        +Int event_id
        +String image_url
        +DateTime upload_date
        +String caption

        +addImage()
        +deleteImage()
        +viewGallery()
    }

    class Notification {
        +Int notification_id
        +Int user_id
        +Int event_id
        +Enum notification_type
        +String message
        +DateTime send_date

        +sendNotification()
        +viewNotification()
        +deleteNotification()
    }

    class JobOffer {
        +Int job_offer_id
        +Int organization_id
        +String title
        +String description
        +DateTime posted_date

        +createJobOffer()
        +updateJobOffer()
        +deleteJobOffer()
        +viewJobOffer()
    }

    Organization "1" --> "0..*" Event : organizes
    User "1" --> "0..*" EventRegistration : registers
    User "1" --> "0..*" Notification : receives
    Event "1" --> "0..*" EventRegistration : has
    Event "1" --> "0..*" EventGallery : contains
    Event "1" --> "0..*" Notification : related to
    Organization "1" --> "0..*" JobOffer : posts
```

## Diagram Description

This class diagram illustrates the data model and relationships between entities in the iTeam University Event Management System:

### Core Entities
- **User**: Represents individual users (students, faculty)
- **Organization**: Represents companies and organizations that can host events
- **Admin**: System administrators with elevated privileges
- **Event**: Events organized through the platform

### Supporting Entities
- **EventRegistration**: Links users to events they register for
- **EventGallery**: Manages images associated with events
- **Notification**: System messages sent to users
- **JobOffer**: Job postings created by organizations

### Key Relationships
1. Organizations organize multiple Events
2. Users register for Events through EventRegistrations
3. Events have multiple EventRegistrations and gallery images
4. Users receive Notifications
5. Organizations post JobOffers

This diagram aligns with the database schema defined in `backend/db/init_db.sql`.