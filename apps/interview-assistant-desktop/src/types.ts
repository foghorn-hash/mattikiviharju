/**
 * Types for i4ware Job Seekers Autopilot AI - Interview Assistant Desktop App
 */

export interface VerifiedProject {
  id: number;
  title: string;
  description: string;
  tech_stack: string;
  url?: string;
}

export interface HRCriteria {
  role: string;
  key_competencies: string;
  evaluation_guidelines: string;
}

export interface InterviewPreparation {
  application_id: number;
  application_title?: string;
  job_title?: string;
  company_name?: string;
  role_title?: string;
  job_description?: string;
  cover_letter?: string;
  motivation_letter?: string;
  language: string;
  mode: 'job_seeker' | 'hr_advisor';
  project_ids: number[];
  verified_projects?: VerifiedProject[];
  profile_summary?: string;
  skills?: string[];
  hr_criteria?: HRCriteria;
  updated_at?: number;
}

export interface AISuggestion {
  intent: string;
  main_point: string;
  supporting_points: string[];
  real_example: string;
  clarifying_question: string;
  speech_draft: string;
  hr_score?: number;
  is_clarification_needed?: boolean;
  mode: 'job_seeker' | 'hr_advisor';
  language: string;
  verified_no_hallucinations: boolean;
}

export interface DevicePairingResponse {
  success: boolean;
  message: string;
  token?: string;
  user?: {
    id: number;
    display_name: string;
    email: string;
  };
  server_info?: {
    site_url: string;
    site_name: string;
    has_openai: boolean;
    has_google_ai: boolean;
    has_liveavatar: boolean;
    liveavatar_id: string;
    liveavatar_voice: string;
  };
}

export interface ExtensionMessage {
  type: 'HANDSHAKE' | 'MEETING_DETECTED' | 'LISTENING_STATE' | 'TRANSCRIPT' | 'DISCONNECT';
  token?: string;
  platform?: 'teams_web' | 'meet_web';
  meetingTitle?: string;
  text?: string;
  speaker?: 'interviewer' | 'candidate';
  isFinal?: boolean;
  timestamp?: number;
}

export interface AppConfig {
  serverUrl: string;
  deviceToken: string;
  pairedUser?: {
    id: number;
    name: string;
    email: string;
  };
  audioRecordingEnabled: boolean;
  loopbackPort: number;
  selectedMicDeviceId?: string;
  selectedSpeakerDeviceId?: string;
}
