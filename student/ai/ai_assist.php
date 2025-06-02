<?php
// ai_assist.php - Student chatbot assistant API
session_start();
header("Content-Type: application/json");

require_once '../api/db_connection.php';

class EventAssistant {
    private $conn;
    private $prompt;
    private $student_id;
    private $account_name;
    private $account_type;
    private $account_id;
    private $recommended_jobs = []; // Add this at the top with other properties
    private $applied_jobs = []; // Also add this for student job applications
    private $registered_events = [];
    private $recommended_events = [];
    private $user_context = "";
    private $api_key;
    private $system_message;
    private $conversation_history = [];
    private $data_validation = [
        'db_connection' => false,
        'events_query_executed' => false,
        'events_found' => false,
        'events_count' => 0,
        'jobs_query_executed' => false, // New validation field
        'jobs_found' => false,         // New validation field
        'jobs_count' => 0,            // New validation field
        'context_built' => false
    ];

    public function __construct($db_connection, $prompt) {
        $this->conn = $db_connection;
        $this->prompt = $prompt;
        
        // Get user information from session
        $this->student_id = $_SESSION['student_id'] ?? null;
        $this->account_name = $_SESSION['account_name'] ?? "Guest";
        $this->account_type = $_SESSION['account_type'] ?? "guest";
        $this->account_id = $_SESSION['account_id'] ?? null;
        
        // Load conversation history
        $this->conversation_history = $_SESSION['ai_conversation_history'] ?? [];
        
        // Load API key
        $this->loadApiKey();
        
        // Verify DB connection
        $this->verifyDatabaseConnection();
    }
    
    public function process() {
        // Get events data
       if ($this->student_id && $this->account_type === 'student') {
        $this->getStudentEvents();
        $this->getStudentJobApplications(); // Add this
    } else {
        $this->getGuestEvents();
        $this->getGuestJobs(); // Add this
    }
        
        // Build system message
        $this->buildSystemMessage();
        
        // Add the new user message to history
        $this->addUserMessageToHistory();
        
        // Log the conversation for debugging
        $this->logConversation();
        
        // Process with AI or generate local response
        if (!$this->api_key) {
            return $this->returnLocalResponse();
        }
        
        try {
            return $this->processWithAPI();
        } catch (Exception $e) {
            error_log("AI Assistant Error: " . $e->getMessage());
            return $this->returnLocalResponse();
        }
    }
    
    private function verifyDatabaseConnection() {
        try {
            $this->conn->query("SELECT 1");
            $this->data_validation['db_connection'] = true;
            
            // Debug data - count events in DB
            $debug_stmt = $this->conn->query("SELECT COUNT(*) FROM events WHERE start_date >= CURDATE()");
            $this->data_validation['total_events_in_db'] = $debug_stmt->fetchColumn();
        } catch (Exception $e) {
            error_log("Database connection error: " . $e->getMessage());
            $this->data_validation['db_connection'] = false;
            $this->data_validation['total_events_in_db'] = 'error';
        }
    }
    
    // 1. Update getGuestEvents() to use the correct column names
private function getGuestEvents() {
    try {
        $sql = "
            SELECT event_id, title, description, start_date, end_date, location, event_type
            FROM events
            WHERE start_date >= CURDATE() 
            ORDER BY start_date ASC
            LIMIT 5
        ";
        
        $stmt = $this->conn->prepare($sql);
        $stmt->execute();
        $this->data_validation['events_query_executed'] = true;
        
        $this->recommended_events = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $this->data_validation['events_found'] = !empty($this->recommended_events);
        $this->data_validation['events_count'] = count($this->recommended_events);
        
        if (!empty($this->recommended_events)) {
            $this->buildGuestEventsContext();
        } else {
            $this->useSampleEvents();
        }
    } catch (PDOException $e) {
        error_log("Error fetching events for guest: " . $e->getMessage());
        $this->user_context = "There was an error retrieving event information. ";
    }
}



private function getStudentJobApplications() {
    try {
        // Get student's job applications
        $stmt = $this->conn->prepare("
            SELECT j.job_offer_id, j.title, j.description, j.posted_date, 
                  o.name as organization_name, a.status, a.application_date
            FROM job_applications a
            JOIN job_offers j ON a.job_offer_id = j.job_offer_id
            JOIN organizations o ON j.organization_id = o.organization_id
            WHERE a.student_id = :student_id
            ORDER BY a.application_date DESC
            LIMIT 5
        ");
        $stmt->bindParam(':student_id', $this->student_id, PDO::PARAM_INT);
        $stmt->execute();
        $this->applied_jobs = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Get recommended jobs (jobs student hasn't applied to)
        $this->getRecommendedJobs();
        
    } catch (PDOException $e) {
        error_log("Error fetching student job applications: " . $e->getMessage());
    }
}

private function getRecommendedJobs() {
    try {
        $applied_job_ids = array_map(function($job) {
            return $job['job_offer_id'];
        }, $this->applied_jobs);
        
        if (!empty($applied_job_ids)) {
            $placeholders = implode(',', array_fill(0, count($applied_job_ids), '?'));
            $sql = "
                SELECT j.job_offer_id, j.title, j.description, j.posted_date,
                      o.name AS organization_name  -- Use AS to explicitly name the column
                FROM job_offers j
                JOIN organizations o ON j.organization_id = o.organization_id
                WHERE j.job_offer_id NOT IN ($placeholders)
                ORDER BY j.posted_date DESC
                LIMIT 5
            ";
            $stmt = $this->conn->prepare($sql);
            // Bind the applied job IDs
            foreach ($applied_job_ids as $index => $job_id) {
                $stmt->bindValue($index + 1, $job_id, PDO::PARAM_INT);
            }
        } else {
            // If student has no applied jobs, recommend any available jobs
            $sql = "
                SELECT j.job_offer_id, j.title, j.description, j.posted_date,
                      o.name AS organization_name  -- Use AS to explicitly name the column
                FROM job_offers j
                JOIN organizations o ON j.organization_id = o.organization_id
                ORDER BY j.posted_date DESC
                LIMIT 5
            ";
            $stmt = $this->conn->prepare($sql);
        }
        
        $stmt->execute();
        $this->recommended_jobs = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $this->data_validation['jobs_found'] = !empty($this->recommended_jobs);
        $this->data_validation['jobs_count'] = count($this->recommended_jobs);
    } catch (PDOException $e) {
        error_log("Error fetching recommended jobs: " . $e->getMessage());
    }
}


private function getGuestJobs() {
    try {
        $sql = "
            SELECT j.*, o.name AS organization_name
              FROM job_offers j
              JOIN organizations o ON j.organization_id = o.organization_id
              ORDER BY j.posted_date DESC
            LIMIT 5
        ";
        
        $stmt = $this->conn->prepare($sql);
        $stmt->execute();
        $this->recommended_jobs = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Debug information
        error_log("DEBUG - Jobs found: " . count($this->recommended_jobs));
        error_log("DEBUG - Jobs data: " . json_encode($this->recommended_jobs));
        
        // Check column names in first job
        if (!empty($this->recommended_jobs)) {
            error_log("DEBUG - First job columns: " . implode(", ", array_keys($this->recommended_jobs[0])));
            
            // Check if organization_name exists
            if (isset($this->recommended_jobs[0]['organization_name'])) {
                error_log("DEBUG - Organization name exists: " . $this->recommended_jobs[0]['organization_name']);
            } else {
                error_log("DEBUG - Organization name missing!");
                // Try to fix it by manually mapping organization names
                $org_query = $this->conn->prepare("SELECT organization_id, name FROM organizations");
                $org_query->execute();
                $organizations = $org_query->fetchAll(PDO::FETCH_KEY_PAIR);
                
                foreach ($this->recommended_jobs as &$job) {
                    if (isset($job['organization_id']) && isset($organizations[$job['organization_id']])) {
                        $job['organization_name'] = $organizations[$job['organization_id']];
                        error_log("DEBUG - Fixed organization name for job {$job['job_offer_id']}: {$job['organization_name']}");
                    }
                }
            }
        }
        
        $this->data_validation['jobs_query_executed'] = true;
        $this->data_validation['jobs_found'] = !empty($this->recommended_jobs);
        $this->data_validation['jobs_count'] = count($this->recommended_jobs);
    } catch (PDOException $e) {
        error_log("Error fetching jobs for guest: " . $e->getMessage());
    }
}


// 2. Update getStudentEvents() to use the correct column names
private function getStudentEvents() {
    try {
        // Get student's registered events
        $stmt = $this->conn->prepare("
            SELECT e.event_id, e.title, e.event_type, e.start_date, e.end_date, e.location, e.description 
            FROM event_registrations er 
            JOIN events e ON er.event_id = e.event_id 
            WHERE er.student_id = :student_id AND e.start_date >= CURDATE()
            ORDER BY e.start_date ASC
            LIMIT 5
        ");
        $stmt->bindParam(':student_id', $this->student_id, PDO::PARAM_INT);
        $stmt->execute();
        $this->data_validation['events_query_executed'] = true;
        
        $this->registered_events = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Get recommended events (events student isn't registered for)
        $this->getRecommendedEvents();
        
        // Build context about the student
        $this->buildStudentContext();
        
    } catch (PDOException $e) {
        error_log("Error fetching student data: " . $e->getMessage());
    }
}

// 3. Update getRecommendedEvents() to use the correct column names
private function getRecommendedEvents() {
    try {
        $registered_event_ids = array_map(function($event) {
            return $event['event_id'];
        }, $this->registered_events);
        
        if (!empty($registered_event_ids)) {
            $placeholders = implode(',', array_fill(0, count($registered_event_ids), '?'));
            $sql = "
                SELECT event_id, title, description, start_date, end_date, location, event_type
                FROM events
                WHERE start_date >= CURDATE() 
                AND event_id NOT IN ($placeholders)
                ORDER BY start_date ASC
                LIMIT 5
            ";
            $stmt = $this->conn->prepare($sql);
            // Bind the registered event IDs
            foreach ($registered_event_ids as $index => $event_id) {
                $stmt->bindValue($index + 1, $event_id, PDO::PARAM_INT);
            }
        } else {
            // If student has no registered events, recommend any upcoming events
            $sql = "
                SELECT event_id, title, description, start_date, end_date, location, event_type
                FROM events
                WHERE start_date >= CURDATE() 
                ORDER BY start_date ASC
                LIMIT 5
            ";
            $stmt = $this->conn->prepare($sql);
        }
        
        $stmt->execute();
        $this->recommended_events = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $this->data_validation['events_found'] = !empty($this->recommended_events);
        $this->data_validation['events_count'] = count($this->recommended_events);
    } catch (PDOException $e) {
        error_log("Error fetching recommended events: " . $e->getMessage());
    }
}

    private function buildStudentContext() {
        $this->user_context = "The student's name is {$this->account_name}. ";
        
        if (!empty($this->registered_events)) {
            $this->user_context .= "They are registered for these upcoming events: ";
            foreach ($this->registered_events as $index => $event) {
                // Format the date and time from DATETIME format
                $event_date = date("F j, Y", strtotime($event['start_date']));
                $start_time = date("g:i A", strtotime($event['start_date']));
                $end_time = isset($event['end_date']) ? date("g:i A", strtotime($event['end_date'])) : "";
                
                $this->user_context .= "{$event['title']} (Event ID: {$event['event_id']}, Type: {$event['event_type']}, " .
                                  "Date: $event_date, Time: $start_time";
                
                if ($end_time) {
                    $this->user_context .= " - $end_time";
                }
                
                $this->user_context .= ", Location: {$event['location']})";
                
                if ($index < count($this->registered_events) - 1) {
                    $this->user_context .= "; ";
                }
            }
            $this->user_context .= ". ";
        } else {
            $this->user_context .= "They are not currently registered for any upcoming events. ";
        }
        
        // After adding event context, add job application context
        if (!empty($this->applied_jobs)) {
            $this->user_context .= "They have applied to these job offers: ";
            foreach ($this->applied_jobs as $index => $job) {
                $posted_date = date("F j, Y", strtotime($job['posted_date']));
                $application_date = date("F j, Y", strtotime($job['application_date']));
                
                $this->user_context .= "{$job['title']} (Job ID: {$job['job_offer_id']}, Organization: {$job['organization_name']}, " .
                                   "Posted: $posted_date, Application Status: {$job['status']}, Applied on: $application_date)";
                
                if ($index < count($this->applied_jobs) - 1) {
                    $this->user_context .= "; ";
                }
            }
            $this->user_context .= ". ";
        } else {
            $this->user_context .= "They haven't applied to any job offers yet. ";
        }
        
        $this->addRecommendedEventsToContext();
        $this->addRecommendedJobsToContext();
    }
    
    private function buildGuestEventsContext() {
        $this->user_context = "Here are some upcoming events on campus: ";
        foreach ($this->recommended_events as $index => $event) {
            // Format the date and time from DATETIME format
            $event_date = date("F j, Y", strtotime($event['start_date']));
            $start_time = date("g:i A", strtotime($event['start_date']));
            $end_time = isset($event['end_date']) ? date("g:i A", strtotime($event['end_date'])) : "";
            
            $event_desc = "";
            if (isset($event['description']) && !empty($event['description'])) {
                $event_desc = substr($event['description'], 0, 100) . (strlen($event['description']) > 100 ? '...' : '');
            }
            
            $this->user_context .= "{$event['title']} (Event ID: {$event['event_id']}, Type: {$event['event_type']}, " .
                              "Date: $event_date, Time: $start_time";
            
            if ($end_time) {
                $this->user_context .= " - $end_time";
            }
            
            $this->user_context .= ", Location: {$event['location']}";
            
            if ($event_desc) {
                $this->user_context .= ", Description: $event_desc";
            }
            $this->user_context .= ")";
            
            if ($index < count($this->recommended_events) - 1) {
                $this->user_context .= "; ";
            }
        }
        $this->user_context .= ". ";
        
        // job offers context
        if (!empty($this->recommended_jobs)) {
            $this->user_context .= "Here are some available job offers: ";
            foreach ($this->recommended_jobs as $index => $job) {
                $posted_date = date("F j, Y", strtotime($job['posted_date']));
                $job_desc = substr($job['description'], 0, 100) . (strlen($job['description']) > 100 ? '...' : '');
                
                $this->user_context .= "{$job['title']} (Job ID: {$job['job_offer_id']}, Organization: {$job['organization_name']}, " .
                                   "Posted: $posted_date, Description: $job_desc)";
                                  
                if ($index < count($this->recommended_jobs) - 1) {
                    $this->user_context .= "; ";
                }
            }
            $this->user_context .= ". ";
        } else {
            $this->user_context .= "There are no job offers to recommend at this moment. ";
        }
        
        $this->data_validation['context_built'] = true;
    }
   
private function useSampleEvents() {
    $this->user_context = "There are no upcoming events to display at the moment. ";
    
    // Check if we have jobs to display
    if (!empty($this->recommended_jobs)) {
        $this->user_context .= "However, there are some job offers available: ";
        foreach ($this->recommended_jobs as $index => $job) {
            $posted_date = date("F j, Y", strtotime($job['posted_date']));
            $job_desc = substr($job['description'], 0, 100) . (strlen($job['description']) > 100 ? '...' : '');
            
            $this->user_context .= "{$job['title']} (Job ID: {$job['job_offer_id']}, Organization: {$job['organization_name']}, " .
                               "Posted: $posted_date, Description: $job_desc)";
                               
            if ($index < count($this->recommended_jobs) - 1) {
                $this->user_context .= "; ";
            }
        }
        $this->user_context .= ". ";
        
        $this->data_validation['jobs_found'] = true;
        $this->data_validation['jobs_count'] = count($this->recommended_jobs);
    } else {
        $this->user_context .= "There are also no job opportunities available at the moment.";
    }
}
    
    private function addRecommendedEventsToContext() {
        if (!empty($this->recommended_events)) {
            $this->user_context .= "Here are some upcoming events they might be interested in: ";
            foreach ($this->recommended_events as $index => $event) {
                // Format the date and time from DATETIME format
                $event_date = date("F j, Y", strtotime($event['start_date']));
                $start_time = date("g:i A", strtotime($event['start_date']));
                $end_time = isset($event['end_date']) ? date("g:i A", strtotime($event['end_date'])) : "";
                
                $event_desc = isset($event['description']) ? substr($event['description'], 0, 100) . (strlen($event['description']) > 100 ? '...' : '') : '';
                
                $this->user_context .= "{$event['title']} (Event ID: {$event['event_id']}, Type: {$event['event_type']}, " .
                                  "Date: $event_date, Time: $start_time";
                
                if ($end_time) {
                    $this->user_context .= " - $end_time";
                }
                
                $this->user_context .= ", Location: {$event['location']}, " .
                                  "Description: $event_desc)";
                                  
                if ($index < count($this->recommended_events) - 1) {
                    $this->user_context .= "; ";
                }
            }
            $this->user_context .= ". ";
        } else {
            $this->user_context .= "There are no upcoming events to recommend at this moment. ";
        }
        
        $this->data_validation['context_built'] = true;
    }
    

private function addRecommendedJobsToContext() {
    if (!empty($this->recommended_jobs)) {
        error_log("DEBUG - Adding " . count($this->recommended_jobs) . " jobs to context");
        $this->user_context .= "Here are some job offers they might be interested in: ";
        foreach ($this->recommended_jobs as $index => $job) {
            // Check for required fields
            if (!isset($job['job_offer_id']) || !isset($job['title']) || !isset($job['posted_date'])) {
                error_log("DEBUG - Job missing required fields: " . json_encode($job));
                continue;
            }
            
            // Handle organization name if it's missing
            $org_name = $job['organization_name'] ?? $job['name'] ?? 'Unknown Organization';
            
            $posted_date = date("F j, Y", strtotime($job['posted_date']));
            $job_desc = isset($job['description']) ? substr($job['description'], 0, 100) . (strlen($job['description']) > 100 ? '...' : '') : 'No description available';
            
            error_log("DEBUG - Adding job to context: {$job['title']} - $org_name");
            
            $this->user_context .= "{$job['title']} (Job ID: {$job['job_offer_id']}, Organization: $org_name, " .
                               "Posted: $posted_date, Description: $job_desc)";
                              
            if ($index < count($this->recommended_jobs) - 1) {
                $this->user_context .= "; ";
            }
        }
        $this->user_context .= ". ";
    } else {
        error_log("DEBUG - No jobs to add to context");
        $this->user_context .= "There are no job offers to recommend at this moment. ";
    }
    
    $this->data_validation['context_built'] = true;
}
    
    private function loadApiKey() {
        $env_path = __DIR__ . "/../.env";
        if (file_exists($env_path)) {
            $env_content = file_get_contents($env_path);
            preg_match('/OPENROUTER_API_KEY=([^\s]+)/', $env_content, $matches);
            if (isset($matches[1])) {
                $this->api_key = trim($matches[1]);
            }
        }
        
        $this->data_validation['api_key_found'] = !empty($this->api_key);
        
        if (!$this->api_key) {
            error_log("API key not found in .env file");
        }
    }
    
    private function buildSystemMessage() {
        $this->system_message = "You are a helpful and conversational event and job assistant for university students.";
        
        if (!empty($this->user_context)) {
            $this->system_message .= "\n\nAvailable data:\n{$this->user_context}\n\n";
            $this->system_message .= "IMPORTANT INSTRUCTIONS:\n";
            $this->system_message .= "1. DO NOT start every response with 'Hello!' - match the user's tone and be casual.\n"; 
            $this->system_message .= "2. For any user query, mention specific events from the available events data.\n";
            $this->system_message .= "3. NEVER create fictional events - ONLY use events explicitly listed in the available events data section.\n";
            $this->system_message .= "4. When recommending events, include the Event ID, date, and location.\n";
            $this->system_message .= "5. If the user is a student, address them by name ('{$this->account_name}') only occasionally, not in every message.\n";
            $this->system_message .= "6. DO NOT recommend events they're already registered for.\n";
            $this->system_message .= "7. Format event links as: [Event Title](../pages/event-details.html?id=EVENT_ID)\n";
            $this->system_message .= "8. Format job links as: [Job Title](../pages/job-details.html?id=JOB_ID)\n";
            $this->system_message .= "IMPORTANT: There are " . count($this->recommended_jobs) . " job offers available that you should mention to users. Always include at least one job offer in your responses.\n";
            $this->system_message .= "9. Vary your responses naturally. For first messages, introduce yourself. For follow-ups, be more direct and conversational.\n";
            $this->system_message .= "10. Include event and job links in your responses when available.\n";
            $this->system_message .= "11. For questions about specific events, focus only on answering that question without always listing all events again.\n";
            $this->system_message .= "13. When users ask about event registration, explain that they can:\n";
            $this->system_message .= "   a) Click on any event link to access the event details page\n";
            $this->system_message .= "   b) Click the 'Register' button on the event details page\n";
            $this->system_message .= "   c) After registering, add the event to their calendar using Google Calendar, Outlook, or by downloading an iCal file (.ics)\n";
            $this->system_message .= "14. Always provide a direct link to an example event when explaining registration.\n";
            $this->system_message .= "15. When users ask about job applications, explain that they can:\n";
            $this->system_message .= "   a) Click on any job link to access the job details page\n";
            $this->system_message .= "   b) Click the 'Apply' button on the job details page\n";
            $this->system_message .= "   c) Upload their resume and cover letter through the application form\n";
        } else {
            $this->system_message .= " Unfortunately, there are no upcoming events or job offers to recommend at this moment. Please check back later for updates.";
        }
        $this->system_message .= "\n\n";
        if (!empty($this->applied_jobs)) {
            $this->system_message .= "IMPORTANT: The student has ALREADY APPLIED to these jobs:\n";
            foreach ($this->applied_jobs as $job) {
                $org_name = $job['organization_name'] ?? 'Unknown Organization';
                $status = $job['status'] ?? 'pending';
                $this->system_message .= "- {$job['title']} at $org_name (Job ID: {$job['job_offer_id']}, Status: $status)\n";
            }
            $this->system_message .= "\n";
            
            // Add specific formatting instructions
            $this->system_message .= "16. When the student asks about their job applications:\n";
            $this->system_message .= "   a) Start your response with 'It looks like you've already applied to the following job positions:'\n";
            $this->system_message .= "   b) List each job on a new line with a dash using exactly this format:\n";
            $this->system_message .= "      - [Job Title] at [Organization]  - Status: [status]\n";
            $this->system_message .= "   c) After listing all applications, mention they can check their status in the my applications\n";
        }
        
        // Add this critical instruction near the beginning
        if (!empty($this->recommended_jobs)) {
            $this->system_message .= "\n\nCRITICAL: When the user asks for 'available jobs', ALWAYS list the following jobs with their exact titles:\n";
            foreach ($this->recommended_jobs as $job) {
                $org_name = $job['organization_name'] ?? $job['name'] ?? 'Unknown Organization';
                $this->system_message .= "- [{$job['title']}](../pages/job-details.html?id={$job['job_offer_id']}) - $org_name\n";
            }
            $this->system_message .= "\nNEVER say there are no jobs available when these jobs exist.\n\n";
        }
    }
    
    private function addUserMessageToHistory() {
        // Add the new user message to history (limit to last 5 messages to save tokens)
        if (count($this->conversation_history) >= 10) {
            array_shift($this->conversation_history); // Remove oldest message
            array_shift($this->conversation_history); // Remove oldest message
        }
        $this->conversation_history[] = ['role' => 'user', 'content' => $this->prompt];
    }
  
    
private function logConversation() {
    $log_file = __DIR__ . '/ai_conversations.log';
    $log_data = [
        'timestamp' => date('Y-m-d H:i:s'),
        'student_id' => $this->student_id ?? 'guest',
        'account_name' => $this->account_name,
        'prompt' => $this->prompt,
        'system_message' => $this->system_message,
        'event_count' => count($this->recommended_events),
        'events_available' => !empty($this->recommended_events),
        'job_count' => count($this->recommended_jobs ?? []),
        'jobs_available' => !empty($this->recommended_jobs),
        'validation' => $this->data_validation,
        'events_data' => $this->recommended_events,
        'jobs_data' => $this->recommended_jobs ?? []
    ];
    file_put_contents($log_file, json_encode($log_data) . "\n", FILE_APPEND);
}
    
    private function returnLocalResponse() {
        $simpleResponse = $this->generateLocalResponse();
        
        $response_data = [
            "id" => "local-" . uniqid(),
            "object" => "chat.completion",
            "created" => time(),
            "model" => "local-response",
            "choices" => [
                [
                    "index" => 0,
                    "message" => [
                        "role" => "assistant",
                        "content" => $simpleResponse
                    ],
                    "finish_reason" => "stop"
                ]
            ]
        ];
        
        return json_encode($response_data);
    }
    
    private function processWithAPI() {
        try {
        $messages = [['role' => 'system', 'content' => $this->system_message]];
        foreach ($this->conversation_history as $message) {
            $messages[] = $message;
        }
        
        $body = [
            "model" => "openai/gpt-3.5-turbo",
            "messages" => $messages,
            "temperature" => 0.7,
            "max_tokens" => 600
        ];
        
        // Add timeout handling
        $ch = curl_init("https://openrouter.ai/api/v1/chat/completions");
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => json_encode($body),
            CURLOPT_HTTPHEADER => [
                "Authorization: Bearer {$this->api_key}",
                "Content-Type: application/json",
                "HTTP-Referer: http://localhost/student"
            ],
            CURLOPT_TIMEOUT => 30, // 30 second timeout
            CURLOPT_CONNECTTIMEOUT => 5 // 5 second connection timeout
        ]);

        $response = curl_exec($ch);
        $curl_error = curl_errno($ch) ? curl_error($ch) : null;
        $status_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        // Log the API response for debugging but don't expose to users
        error_log("API response: Status $status_code, Error: $curl_error");
        
        // Handle connection errors with friendly message
        if ($curl_error || $status_code != 200) {
            error_log("API request failed: $curl_error, Status: $status_code");
            return $this->returnLocalResponse();
        }

        // Handle invalid JSON responses
        $response_data = json_decode($response, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            error_log("Invalid JSON response: " . json_last_error_msg());
            return $this->returnLocalResponse();
        }

        // Handle missing data in the response
        if (!$response_data || !isset($response_data['choices'][0]['message']['content'])) {
            error_log("Invalid API response format: Missing expected fields");
            return $this->returnLocalResponse();
        }

        // Process successful response
        $ai_response = $response_data['choices'][0]['message']['content'];
        $this->conversation_history[] = ['role' => 'assistant', 'content' => $ai_response];
        $_SESSION['ai_conversation_history'] = $this->conversation_history;

        return $response;
        
    } catch (Exception $e) {
        // Catch any unexpected exceptions
        error_log("Exception in API processing: " . $e->getMessage());
        return $this->returnLocalResponse();
    }
}
    
    private function generateLocalResponse() {
        // Check if asking about job applications
        if ($this->isJobApplicationQuestion()) {
            if (empty($this->recommended_jobs)) {
                return "To apply for a job, click on the job link, then click the 'Apply' button on the job details page. You'll need to upload your resume and potentially write a cover letter.";
            }
            
            // Get a random job to use as an example
            $random_job = $this->recommended_jobs[array_rand($this->recommended_jobs)];
            $posted_date = date("F j, Y", strtotime($random_job['posted_date']));
            
            return "To apply for a job like [{$random_job['title']}](../pages/job-details.html?id={$random_job['job_offer_id']}), click the job link, then tap the 'Apply' button. Upload your resume, write a cover letter that highlights your relevant skills, and submit your application. After applying, you can track its status in your dashboard.";
        }
        
        // Check if specifically asking about jobs
        if ($this->isJobQuestion()) {
            if (empty($this->recommended_jobs)) {
                return "I don't see any job offers available at the moment. Check back soon for new opportunities!";
            }
            
            $response = "Here are some job opportunities you might be interested in:\n\n";
            foreach(array_slice($this->recommended_jobs, 0, 3) as $job) {
                $posted_date = date("F j, Y", strtotime($job['posted_date']));
                
                $response .= "• [{$job['title']}](../pages/job-details.html?id={$job['job_offer_id']}) - {$job['organization_name']}, posted on $posted_date\n";
            }
            return $response;
        }
        
        // For greetings or other queries, include both events and jobs
        if (empty($this->recommended_events)) {
            return "Hi there! I'm your event assistant but I don't see any upcoming events at the moment.";
        }
        
        $response = "Hi! Here are some events coming up:\n\n";
        foreach(array_slice($this->recommended_events, 0, 3) as $event) {
            $event_date = date("F j, Y", strtotime($event['start_date']));
            $event_time = date("g:i A", strtotime($event['start_date']));
            
            $response .= "• [{$event['title']}](../pages/event-details.html?id={$event['event_id']}) - {$event['event_type']} on $event_date at $event_time in {$event['location']}\n";
        }
        
        if (!empty($this->recommended_jobs)) {
            $response .= "\n\nAlso check out these job opportunities:\n\n";
            foreach(array_slice($this->recommended_jobs, 0, 2) as $job) {
                $posted_date = date("F j, Y", strtotime($job['posted_date']));
                $response .= "• [{$job['title']}](../pages/job-details.html?id={$job['job_offer_id']}) - {$job['organization_name']}, posted on $posted_date\n";
            }
        }
        
        return $response;
    }
    
    private function isRegistrationQuestion() {
        $patterns = [
            '/how.*(register|sign.?up|join|attend|participate|enroll)/i',
            '/register.*event/i',
            '/sign.?up.*event/i',
            '/add.*calendar/i',
            '/can i.*(register|sign.?up|join|attend|participate)/i',
        ];
        
        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $this->prompt)) {
                return true;
            }
        }
        
        return false;
    }
    
    private function isJobQuestion() {

        
        // First check if it's about existing applications to avoid overlap
        if ($this->isAppliedJobsQuestion()) {
            return false;
        }
        
        $patterns = [
            '/job/i',
            '/career/i',
            '/employment/i',
            '/hiring/i',
            '/position/i',
            '/work/i',
            '/internship/i',
        ];
        
        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $this->prompt)) {
                return true;
            }
        }
        
        return false;
    }
    
    private function isJobApplicationQuestion() {
        $patterns = [
            '/how.*(apply|submit|send).*(job|position|application)/i',
            '/apply.*job/i',
            '/job.*application/i',
            '/apply.*position/i',
            '/upload.*resume/i',
            '/cover.*letter/i',
            '/can i.*(apply|submit|send)/i',
        ];
        
        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $this->prompt)) {
                return true;
            }
        }
        
        return false;
    }
    
// Add this method to detect questions about applied jobs
private function isAppliedJobsQuestion() {
    if (empty($this->applied_jobs)) {
        return false; // No point checking if student hasn't applied to any jobs
    }
    
    $patterns = [
        '/my.*application/i',
        '/applied.*job/i',
        '/job.*applied/i',
        '/application.*status/i',
        '/status.*application/i',
        '/check.*application/i',
        '/my.*resume/i',
        '/submitted.*application/i',
    ];
    
    // Also check if they're asking about a specific job they applied to
    foreach ($this->applied_jobs as $job) {
        $job_title_pattern = '/' . preg_quote($job['title'], '/') . '/i';
        if (preg_match($job_title_pattern, $this->prompt)) {
            return true;
        }
    }
    
    foreach ($patterns as $pattern) {
        if (preg_match($pattern, $this->prompt)) {
            return true;
        }
    }
    
    return false;
}
}

// Main execution
$input = json_decode(file_get_contents("php://input"), true);
$prompt = $input["prompt"] ?? "";

if (!$prompt) {
    http_response_code(400);
    echo json_encode(["error" => "Missing prompt"]);
    exit;
}

$assistant = new EventAssistant($conn, $prompt);
echo $assistant->process();

// Add this at the top of your file, after the class declaration
set_error_handler(function($severity, $message, $file, $line) {
    if (error_reporting() & $severity) {
        // Log the error but don't expose it
        error_log("PHP Error: $message in $file on line $line");
        
        // Return a friendly error response instead of crashing
        http_response_code(500);
        echo json_encode([
            "id" => "error-" . uniqid(),
            "object" => "chat.completion",
            "created" => time(),
            "model" => "error-handler",
            "choices" => [
                [
                    "index" => 0,
                    "message" => [
                        "role" => "assistant",
                        "content" => "I'm having trouble processing your request right now. Please try again in a moment."
                    ],
                    "finish_reason" => "stop"
                ]
            ]
        ]);
        exit;
    }
    return false; // Let PHP handle errors not caught here
});