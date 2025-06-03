# iTeam University - Class Diagram

The following diagram illustrates the class structure and relationships between entities in the iTeam University Event Management System.

```mermaid
@startuml
' Title
title Event Management - Class Diagram

' Styling
skinparam classAttributeIconSize 0

' Entities
class students {
  +student_id : INT
  +first_name : VARCHAR
  +last_name : VARCHAR
  +email : VARCHAR
  +password : VARCHAR
  +profile_picture : VARCHAR
  +registration_date : DATETIME
  +status : ENUM
  +created_at : DATETIME
  +updated_at : DATETIME
}

class organizations {
  +organization_id : INT
  +name : VARCHAR
  +email : VARCHAR
  +password : VARCHAR
  +description : TEXT
  +profile_picture : VARCHAR
  +registration_date : DATETIME
  +status : ENUM
  +created_at : DATETIME
  +updated_at : DATETIME
}

class admins {
  +admin_id : INT
  +username : VARCHAR
  +email : VARCHAR
  +password : VARCHAR
  +created_at : DATETIME
  +updated_at : DATETIME
}

class accounts {
  +account_id : INT
  +account_type : ENUM
  +reference_id : INT
  +last_login : DATETIME
  +created_at : DATETIME
  +updated_at : DATETIME
}

class events {
  +event_id : INT
  +title : VARCHAR
  +description : TEXT
  +start_date : DATETIME
  +end_date : DATETIME
  +location : VARCHAR
  +event_type : ENUM
  +max_capacity : INT
  +organizer_id : INT
  +requires_approval : BOOLEAN
  +thumbnail_url : VARCHAR
  +created_at : DATETIME
  +updated_at : DATETIME
}

class event_registrations {
  +registration_id : INT
  +event_id : INT
  +student_id : INT
  +registration_date : DATETIME
  +status : ENUM
  +created_at : DATETIME
  +updated_at : DATETIME
}

class event_gallery {
  +image_id : INT
  +event_id : INT
  +image_url : VARCHAR
  +upload_date : DATETIME
  +caption : VARCHAR
  +created_at : DATETIME
  +updated_at : DATETIME
}

class notifications {
  +notification_id : INT
  +account_id : INT
  +event_id : INT
  +notification_type : ENUM
  +message : VARCHAR
  +is_read : BOOLEAN
  +send_date : DATETIME
  +created_at : DATETIME
  +updated_at : DATETIME
}

class job_offers {
  +job_offer_id : INT
  +organization_id : INT
  +title : VARCHAR
  +description : TEXT
  +posted_date : DATETIME
  +created_at : DATETIME
  +updated_at : DATETIME
}

class job_applications {
  +application_id : INT
  +job_offer_id : INT
  +student_id : INT
  +application_date : DATETIME
  +cover_letter : TEXT
  +resume_path : VARCHAR
  +status : ENUM
  +notes : TEXT
  +created_at : DATETIME
  +updated_at : DATETIME
}

' Relationships
students "1" -- "0..*" event_registrations : registers
students "1" -- "0..*" job_applications : applies

organizations "1" -- "0..*" events : organizes
organizations "1" -- "0..*" job_offers : posts

events "1" -- "0..*" event_registrations : has
events "1" -- "0..*" event_gallery : contains
events "1" -- "0..*" notifications : notifies

event_gallery "0..*" -- "1" events

accounts "1" -- "0..*" notifications : receives

job_offers "1" -- "0..*" job_applications : receives

@enduml



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