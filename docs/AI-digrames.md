@startuml
title Event Management + AI Assistant Integration

' Existing DB Entities (shortened for clarity)
class students
class events
class event_registrations
class job_offers
class job_applications
class organizations
class accounts

' AI Assistant component
class AI_Assistant {
  +process()
  +recommended_events : List
  +recommended_jobs : List
  +registered_events : List
  +applied_jobs : List
  +buildSystemMessage()
}

' Relationships: Data dependencies only
AI_Assistant --> students : reads
AI_Assistant --> events : reads
AI_Assistant --> event_registrations : reads
AI_Assistant --> job_offers : reads
AI_Assistant --> job_applications : reads
AI_Assistant --> organizations : reads
AI_Assistant --> accounts : reads

@enduml


