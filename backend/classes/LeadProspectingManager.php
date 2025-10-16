<?php
/**
 * Lead Prospecting Manager
 * 
 * Automated lead discovery and AI-powered outreach
 * Part of Rocket Site Plan - Phase 4: Lead Prospecting System
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/APIKeyManager.php';
require_once __DIR__ . '/OpenAIIntegration.php';

class LeadProspectingManager {
    private $db;
    private $conn;
    private $apiKeyManager;
    private $openai;
    
    public function __construct() {
        $this->db = new Database();
        $this->conn = $this->db->getConnection();
        $this->apiKeyManager = new APIKeyManager();
        $this->openai = new OpenAIIntegration();
    }
    
    /**
     * Find leads based on criteria
     */
    public function findLeads($criteria) {
        $leads = [];
        
        // Use Hunter.io to find emails
        if ($this->apiKeyManager->getAPIKey('hunter', true)) {
            $hunterLeads = $this->searchHunter($criteria);
            $leads = array_merge($leads, $hunterLeads);
        }
        
        // Use Clearbit for company data
        if ($this->apiKeyManager->getAPIKey('clearbit', true)) {
            $leads = $this->enrichLeadsWithClearbit($leads);
        }
        
        // Save leads to database
        foreach ($leads as $lead) {
            $this->saveLead($lead);
        }
        
        return [
            'success' => true,
            'count' => count($leads),
            'leads' => $leads
        ];
    }
    
    /**
     * Generate personalized outreach messages
     */
    public function generateOutreachMessage($leadData, $approach = 'direct') {
        $approaches = [
            'direct' => 'Direct value proposition',
            'social_proof' => 'Social proof and testimonials',
            'problem_solution' => 'Problem-solution framework',
            'storytelling' => 'Storytelling approach'
        ];
        
        $prompt = "Generate a personalized cold outreach message for:

Lead Information:
- Name: {$leadData['name']}
- Company: {$leadData['company']}
- Role: {$leadData['role']}
- Industry: {$leadData['industry']}

Our Service: Professional design services (logos, YouTube thumbnails, video editing)

Approach: {$approaches[$approach]}

Create a compelling outreach message that:
1. Personalizes based on their company/industry
2. Addresses a likely pain point
3. Shows value quickly
4. Has a clear, low-friction call-to-action
5. Keeps it brief (under 150 words)

Return as JSON with:
- subject: Email subject line
- message: Email body
- linkedin_message: Shorter LinkedIn message version
- follow_up: Follow-up message for if no response";

        $result = $this->openai->generateEmailTemplate('outreach', $leadData, $prompt);
        
        if ($result['success']) {
            // Save outreach template
            $this->saveOutreachTemplate($leadData['id'], $approach, $result['data']);
        }
        
        return $result;
    }
    
    /**
     * Score lead quality using AI
     */
    public function scoreLeadQuality($leadData) {
        $prompt = "Analyze this lead and score from 1-10:

Company: {$leadData['company']}
Industry: {$leadData['industry']}
Role: {$leadData['role']}
Company Size: {$leadData['company_size']}
Website: {$leadData['website']}

Score based on:
1. Likelihood to need design services
2. Budget capacity
3. Decision-making authority
4. Company growth stage

Return JSON: {\"score\": 8, \"reasoning\": \"...\", \"approach_recommendation\": \"...\"}";

        $result = $this->openai->generateContent($prompt);
        
        return $result;
    }
    
    /**
     * Save lead to database
     */
    private function saveLead($leadData) {
        try {
            $stmt = $this->conn->prepare("
                INSERT INTO leads (
                    name, email, company, role, industry, 
                    company_size, website, phone, status, source,
                    lead_score, created_at
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'new', 'prospecting', ?, datetime('now'))
                ON CONFLICT(email) DO UPDATE SET
                    company = excluded.company,
                    role = excluded.role,
                    updated_at = datetime('now')
            ");
            
            $stmt->execute([
                $leadData['name'] ?? '',
                $leadData['email'] ?? '',
                $leadData['company'] ?? '',
                $leadData['role'] ?? '',
                $leadData['industry'] ?? '',
                $leadData['company_size'] ?? '',
                $leadData['website'] ?? '',
                $leadData['phone'] ?? '',
                $leadData['score'] ?? null
            ]);
            
            return $this->conn->lastInsertId();
            
        } catch (PDOException $e) {
            error_log('Save lead error: ' . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Get leads with filters
     */
    public function getLeads($filters = [], $limit = 50, $offset = 0) {
        try {
            $sql = "SELECT * FROM leads WHERE 1=1";
            $params = [];
            
            if (isset($filters['status'])) {
                $sql .= " AND status = ?";
                $params[] = $filters['status'];
            }
            
            if (isset($filters['industry'])) {
                $sql .= " AND industry = ?";
                $params[] = $filters['industry'];
            }
            
            if (isset($filters['min_score'])) {
                $sql .= " AND lead_score >= ?";
                $params[] = $filters['min_score'];
            }
            
            $sql .= " ORDER BY created_at DESC LIMIT ? OFFSET ?";
            $params[] = $limit;
            $params[] = $offset;
            
            $stmt = $this->conn->prepare($sql);
            $stmt->execute($params);
            
            return [
                'success' => true,
                'data' => $stmt->fetchAll(PDO::FETCH_ASSOC)
            ];
            
        } catch (PDOException $e) {
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Update lead status
     */
    public function updateLeadStatus($leadId, $status, $notes = null) {
        try {
            $stmt = $this->conn->prepare("
                UPDATE leads 
                SET status = ?, notes = ?, updated_at = datetime('now')
                WHERE id = ?
            ");
            
            $stmt->execute([$status, $notes, $leadId]);
            
            return [
                'success' => true,
                'message' => 'Lead status updated'
            ];
            
        } catch (PDOException $e) {
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Helper methods for API integrations
     */
    private function searchHunter($criteria) {
        // Implement Hunter.io API search
        return [];
    }
    
    private function enrichLeadsWithClearbit($leads) {
        // Implement Clearbit enrichment
        return $leads;
    }
    
    private function saveOutreachTemplate($leadId, $approach, $template) {
        try {
            $stmt = $this->conn->prepare("
                INSERT INTO lead_outreach_templates (
                    lead_id, approach, template_data, created_at
                ) VALUES (?, ?, ?, datetime('now'))
            ");
            
            $stmt->execute([
                $leadId,
                $approach,
                json_encode($template)
            ]);
            
        } catch (PDOException $e) {
            error_log('Save outreach template error: ' . $e->getMessage());
        }
    }
}
