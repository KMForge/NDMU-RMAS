--
-- PostgreSQL database dump
--


-- Dumped from database version 17.11
-- Dumped by pg_dump version 17.11

SET statement_timeout = 0;
SET lock_timeout = 0;
SET idle_in_transaction_session_timeout = 0;
SET transaction_timeout = 0;
SET client_encoding = 'UTF8';
SET standard_conforming_strings = on;
SELECT pg_catalog.set_config('search_path', '', false);
SET check_function_bodies = false;
SET xmloption = content;
SET client_min_messages = warning;
SET row_security = off;

--
-- Name: prevent_audit_log_mutation(); Type: FUNCTION; Schema: public; Owner: -
--

CREATE FUNCTION public.prevent_audit_log_mutation() RETURNS trigger
    LANGUAGE plpgsql
    AS $$
BEGIN
    IF TG_OP = 'DELETE' THEN
        RAISE EXCEPTION 'audit logs are append-only';
    END IF;

    IF OLD.user_id IS NOT NULL
        AND NEW.user_id IS NULL
        AND (to_jsonb(OLD) - 'user_id') = (to_jsonb(NEW) - 'user_id') THEN
        RETURN NEW;
    END IF;

    RAISE EXCEPTION 'audit logs are append-only';
END;
$$;


SET default_tablespace = '';

SET default_table_access_method = heap;

--
-- Name: academic_terms; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.academic_terms (
    id bigint NOT NULL,
    academic_year_id bigint NOT NULL,
    name character varying(50) NOT NULL,
    starts_at date NOT NULL,
    ends_at date NOT NULL,
    is_current boolean DEFAULT false NOT NULL,
    created_at timestamp(0) with time zone,
    updated_at timestamp(0) with time zone
);


--
-- Name: academic_terms_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.academic_terms_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: academic_terms_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.academic_terms_id_seq OWNED BY public.academic_terms.id;


--
-- Name: academic_years; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.academic_years (
    id bigint NOT NULL,
    name character varying(30) NOT NULL,
    starts_at date NOT NULL,
    ends_at date NOT NULL,
    is_current boolean DEFAULT false NOT NULL,
    created_at timestamp(0) with time zone,
    updated_at timestamp(0) with time zone
);


--
-- Name: academic_years_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.academic_years_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: academic_years_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.academic_years_id_seq OWNED BY public.academic_years.id;


--
-- Name: adviser_assignments; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.adviser_assignments (
    id bigint NOT NULL,
    research_project_id bigint NOT NULL,
    adviser_id bigint NOT NULL,
    assigned_by bigint,
    status character varying(30) DEFAULT 'active'::character varying NOT NULL,
    remarks text,
    assigned_at timestamp(0) with time zone DEFAULT CURRENT_TIMESTAMP NOT NULL,
    ended_at timestamp(0) with time zone,
    created_at timestamp(0) with time zone,
    updated_at timestamp(0) with time zone
);


--
-- Name: adviser_assignments_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.adviser_assignments_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: adviser_assignments_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.adviser_assignments_id_seq OWNED BY public.adviser_assignments.id;


--
-- Name: audit_logs; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.audit_logs (
    id bigint NOT NULL,
    user_id bigint,
    actor_name character varying(255),
    actor_email character varying(255),
    event character varying(120) NOT NULL,
    auditable_type character varying(255),
    auditable_id bigint,
    description text,
    old_values json,
    new_values json,
    ip_address character varying(45),
    user_agent text,
    created_at timestamp(0) with time zone DEFAULT CURRENT_TIMESTAMP NOT NULL,
    subject_name character varying(255),
    subject_email character varying(255),
    actor_context character varying(64),
    outcome character varying(16) DEFAULT 'succeeded'::character varying NOT NULL
);


--
-- Name: audit_logs_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.audit_logs_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: audit_logs_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.audit_logs_id_seq OWNED BY public.audit_logs.id;


--
-- Name: cache; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.cache (
    key character varying(255) NOT NULL,
    value text NOT NULL,
    expiration bigint NOT NULL
);


--
-- Name: cache_locks; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.cache_locks (
    key character varying(255) NOT NULL,
    owner character varying(255) NOT NULL,
    expiration bigint NOT NULL
);


--
-- Name: colleges; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.colleges (
    id bigint NOT NULL,
    code character varying(30) NOT NULL,
    name character varying(255) NOT NULL,
    is_active boolean DEFAULT true NOT NULL,
    created_at timestamp(0) with time zone,
    updated_at timestamp(0) with time zone
);


--
-- Name: colleges_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.colleges_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: colleges_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.colleges_id_seq OWNED BY public.colleges.id;


--
-- Name: consultation_attendances; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.consultation_attendances (
    id bigint NOT NULL,
    consultation_record_id bigint NOT NULL,
    student_id bigint NOT NULL,
    attended boolean DEFAULT true NOT NULL,
    created_at timestamp(0) with time zone,
    updated_at timestamp(0) with time zone
);


--
-- Name: consultation_attendances_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.consultation_attendances_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: consultation_attendances_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.consultation_attendances_id_seq OWNED BY public.consultation_attendances.id;


--
-- Name: consultation_audits; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.consultation_audits (
    id bigint NOT NULL,
    consultation_request_id bigint NOT NULL,
    research_class_group_id bigint NOT NULL,
    actor_id bigint NOT NULL,
    action character varying(50) NOT NULL,
    status character varying(20) NOT NULL,
    ip_address character varying(45),
    occurred_at timestamp(0) with time zone NOT NULL,
    metadata json,
    created_at timestamp(0) with time zone,
    updated_at timestamp(0) with time zone
);


--
-- Name: consultation_audits_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.consultation_audits_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: consultation_audits_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.consultation_audits_id_seq OWNED BY public.consultation_audits.id;


--
-- Name: consultation_records; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.consultation_records (
    id bigint NOT NULL,
    consultation_request_id bigint NOT NULL,
    research_class_group_id bigint NOT NULL,
    conducted_by bigint NOT NULL,
    consulted_at timestamp(0) with time zone NOT NULL,
    duration_minutes integer DEFAULT 60 NOT NULL,
    consultation_mode character varying(20) NOT NULL,
    location text,
    meeting_url text,
    agenda text NOT NULL,
    discussion text NOT NULL,
    recommendations text,
    next_consultation_at timestamp(0) with time zone,
    supersedes_record_id bigint,
    is_superseded boolean DEFAULT false NOT NULL,
    correction_reason text,
    created_at timestamp(0) with time zone,
    updated_at timestamp(0) with time zone
);


--
-- Name: consultation_records_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.consultation_records_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: consultation_records_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.consultation_records_id_seq OWNED BY public.consultation_records.id;


--
-- Name: consultation_requests; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.consultation_requests (
    id bigint NOT NULL,
    research_project_id bigint,
    adviser_assignment_id bigint,
    requested_by bigint NOT NULL,
    request_token uuid NOT NULL,
    preferred_at timestamp(0) with time zone NOT NULL,
    consultation_mode character varying(20) NOT NULL,
    agenda text NOT NULL,
    status character varying(20) DEFAULT 'pending'::character varying NOT NULL,
    reviewed_by bigint,
    reviewed_at timestamp(0) with time zone,
    review_notes text,
    created_at timestamp(0) with time zone,
    updated_at timestamp(0) with time zone,
    research_class_group_id bigint,
    assigned_adviser_id bigint,
    confirmed_start_at timestamp(0) with time zone,
    confirmed_end_at timestamp(0) with time zone,
    duration_minutes integer DEFAULT 60 NOT NULL,
    location text,
    meeting_url text,
    document_stage character varying(40),
    document_id bigint,
    cancelled_by bigint,
    cancelled_at timestamp(0) with time zone,
    cancellation_reason text
);


--
-- Name: consultation_requests_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.consultation_requests_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: consultation_requests_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.consultation_requests_id_seq OWNED BY public.consultation_requests.id;


--
-- Name: consultation_schedule_proposals; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.consultation_schedule_proposals (
    id bigint NOT NULL,
    consultation_request_id bigint NOT NULL,
    proposed_by bigint NOT NULL,
    proposed_start_at timestamp(0) with time zone NOT NULL,
    duration_minutes integer DEFAULT 60 NOT NULL,
    reason text,
    status character varying(20) DEFAULT 'pending_response'::character varying NOT NULL,
    responded_by bigint,
    responded_at timestamp(0) with time zone,
    created_at timestamp(0) with time zone,
    updated_at timestamp(0) with time zone
);


--
-- Name: consultation_schedule_proposals_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.consultation_schedule_proposals_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: consultation_schedule_proposals_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.consultation_schedule_proposals_id_seq OWNED BY public.consultation_schedule_proposals.id;


--
-- Name: defense_evaluation_round_panelists; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.defense_evaluation_round_panelists (
    id bigint NOT NULL,
    defense_evaluation_round_id bigint NOT NULL,
    defense_panel_assignment_id bigint NOT NULL,
    panelist_user_id bigint NOT NULL,
    "position" smallint NOT NULL,
    created_at timestamp(0) without time zone DEFAULT CURRENT_TIMESTAMP NOT NULL
);


--
-- Name: defense_evaluation_round_panelists_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.defense_evaluation_round_panelists_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: defense_evaluation_round_panelists_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.defense_evaluation_round_panelists_id_seq OWNED BY public.defense_evaluation_round_panelists.id;


--
-- Name: defense_evaluation_round_students; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.defense_evaluation_round_students (
    id bigint NOT NULL,
    defense_evaluation_round_id bigint NOT NULL,
    student_id bigint NOT NULL,
    student_name_snapshot character varying(255) NOT NULL,
    group_member_id bigint,
    created_at timestamp(0) without time zone DEFAULT CURRENT_TIMESTAMP NOT NULL
);


--
-- Name: defense_evaluation_round_students_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.defense_evaluation_round_students_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: defense_evaluation_round_students_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.defense_evaluation_round_students_id_seq OWNED BY public.defense_evaluation_round_students.id;


--
-- Name: defense_evaluation_rounds; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.defense_evaluation_rounds (
    id bigint NOT NULL,
    defense_id bigint NOT NULL,
    defense_schedule_id bigint NOT NULL,
    research_class_group_id bigint NOT NULL,
    status character varying(255) DEFAULT 'open'::character varying NOT NULL,
    summary_signer_user_id bigint,
    opened_by bigint NOT NULL,
    opened_at timestamp(0) without time zone NOT NULL,
    all_submitted_at timestamp(0) without time zone,
    finalized_at timestamp(0) without time zone,
    released_at timestamp(0) without time zone,
    released_by bigint,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    program_code character varying(16)
);


--
-- Name: defense_evaluation_rounds_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.defense_evaluation_rounds_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: defense_evaluation_rounds_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.defense_evaluation_rounds_id_seq OWNED BY public.defense_evaluation_rounds.id;


--
-- Name: defense_evaluation_student_scores; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.defense_evaluation_student_scores (
    id bigint NOT NULL,
    defense_evaluation_id bigint NOT NULL,
    round_student_id bigint NOT NULL,
    student_id bigint NOT NULL,
    communication_score numeric(5,2),
    organization_score numeric(5,2),
    effectiveness_score numeric(5,2),
    presentation_total numeric(5,2),
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    presentation_criterion_scores json
);


--
-- Name: defense_evaluation_student_scores_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.defense_evaluation_student_scores_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: defense_evaluation_student_scores_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.defense_evaluation_student_scores_id_seq OWNED BY public.defense_evaluation_student_scores.id;


--
-- Name: defense_evaluation_student_summaries; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.defense_evaluation_student_summaries (
    id bigint NOT NULL,
    defense_evaluation_summary_id bigint NOT NULL,
    round_student_id bigint NOT NULL,
    student_id bigint NOT NULL,
    presentation_average numeric(5,2) NOT NULL,
    created_at timestamp(0) without time zone DEFAULT CURRENT_TIMESTAMP NOT NULL
);


--
-- Name: defense_evaluation_student_summaries_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.defense_evaluation_student_summaries_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: defense_evaluation_student_summaries_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.defense_evaluation_student_summaries_id_seq OWNED BY public.defense_evaluation_student_summaries.id;


--
-- Name: defense_evaluation_summaries; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.defense_evaluation_summaries (
    id bigint NOT NULL,
    defense_evaluation_round_id bigint NOT NULL,
    research_paper_average numeric(5,2) NOT NULL,
    status character varying(255) DEFAULT 'calculated'::character varying NOT NULL,
    finalized_at timestamp(0) without time zone,
    signed_at timestamp(0) without time zone,
    released_at timestamp(0) without time zone,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


--
-- Name: defense_evaluation_summaries_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.defense_evaluation_summaries_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: defense_evaluation_summaries_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.defense_evaluation_summaries_id_seq OWNED BY public.defense_evaluation_summaries.id;


--
-- Name: defense_evaluations; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.defense_evaluations (
    id bigint NOT NULL,
    defense_evaluation_round_id bigint NOT NULL,
    round_panelist_id bigint NOT NULL,
    panelist_user_id bigint NOT NULL,
    status character varying(255) DEFAULT 'draft'::character varying NOT NULL,
    research_quality_score numeric(5,2),
    originality_score numeric(5,2),
    relevance_score numeric(5,2),
    research_paper_total numeric(5,2),
    general_comments text,
    recommendations text,
    submitted_at timestamp(0) without time zone,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    paper_criterion_scores json,
    rubric_version character varying(32)
);


--
-- Name: defense_evaluations_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.defense_evaluations_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: defense_evaluations_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.defense_evaluations_id_seq OWNED BY public.defense_evaluations.id;


--
-- Name: defense_panel_assignments; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.defense_panel_assignments (
    id bigint NOT NULL,
    defense_id bigint NOT NULL,
    user_id bigint NOT NULL,
    assigned_by bigint NOT NULL,
    assigned_at timestamp(0) without time zone NOT NULL,
    ended_at timestamp(0) without time zone,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    panel_position character varying(32),
    change_reason text
);


--
-- Name: defense_panel_assignments_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.defense_panel_assignments_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: defense_panel_assignments_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.defense_panel_assignments_id_seq OWNED BY public.defense_panel_assignments.id;


--
-- Name: defense_rooms; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.defense_rooms (
    id bigint NOT NULL,
    code character varying(255) NOT NULL,
    name character varying(255) NOT NULL,
    location_notes text,
    is_active boolean DEFAULT true NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


--
-- Name: defense_rooms_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.defense_rooms_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: defense_rooms_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.defense_rooms_id_seq OWNED BY public.defense_rooms.id;


--
-- Name: defense_schedules; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.defense_schedules (
    id bigint NOT NULL,
    defense_id bigint NOT NULL,
    room_id bigint NOT NULL,
    starts_at timestamp(0) without time zone NOT NULL,
    ends_at timestamp(0) without time zone NOT NULL,
    status character varying(255) DEFAULT 'current'::character varying NOT NULL,
    scheduled_by bigint NOT NULL,
    reason text,
    supersedes_schedule_id bigint,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    defense_session_id bigint,
    presentation_order smallint
);


--
-- Name: defense_schedules_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.defense_schedules_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: defense_schedules_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.defense_schedules_id_seq OWNED BY public.defense_schedules.id;


--
-- Name: defense_sessions; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.defense_sessions (
    id bigint NOT NULL,
    research_class_id bigint,
    defense_type character varying(50) NOT NULL,
    room_id bigint NOT NULL,
    session_date date NOT NULL,
    starts_at timestamp(0) without time zone NOT NULL,
    ends_at timestamp(0) without time zone NOT NULL,
    status character varying(30) DEFAULT 'current'::character varying NOT NULL,
    scheduled_by bigint,
    notes text,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


--
-- Name: defense_sessions_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.defense_sessions_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: defense_sessions_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.defense_sessions_id_seq OWNED BY public.defense_sessions.id;


--
-- Name: defenses; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.defenses (
    id bigint NOT NULL,
    research_class_group_id bigint NOT NULL,
    defense_type character varying(255) NOT NULL,
    status character varying(255) DEFAULT 'scheduled'::character varying NOT NULL,
    created_by bigint NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    current_schedule_id bigint,
    completed_at timestamp(0) without time zone,
    completed_by bigint
);


--
-- Name: defenses_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.defenses_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: defenses_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.defenses_id_seq OWNED BY public.defenses.id;


--
-- Name: departments; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.departments (
    id bigint NOT NULL,
    college_id bigint NOT NULL,
    code character varying(30) NOT NULL,
    name character varying(255) NOT NULL,
    is_active boolean DEFAULT true NOT NULL,
    created_at timestamp(0) with time zone,
    updated_at timestamp(0) with time zone
);


--
-- Name: departments_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.departments_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: departments_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.departments_id_seq OWNED BY public.departments.id;


--
-- Name: document_access_audits; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.document_access_audits (
    id bigint NOT NULL,
    document_id bigint NOT NULL,
    user_id bigint,
    action character varying(16) NOT NULL,
    ip_address character varying(45),
    user_agent character varying(1000),
    accessed_at timestamp(0) with time zone NOT NULL,
    created_at timestamp(0) with time zone,
    updated_at timestamp(0) with time zone
);


--
-- Name: document_access_audits_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.document_access_audits_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: document_access_audits_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.document_access_audits_id_seq OWNED BY public.document_access_audits.id;


--
-- Name: document_review_audits; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.document_review_audits (
    id bigint NOT NULL,
    document_id bigint NOT NULL,
    reviewer_id bigint NOT NULL,
    student_id bigint NOT NULL,
    action character varying(40) NOT NULL,
    decision character varying(32),
    ip_address character varying(45),
    occurred_at timestamp(0) with time zone NOT NULL,
    metadata json,
    created_at timestamp(0) with time zone,
    updated_at timestamp(0) with time zone
);


--
-- Name: document_review_audits_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.document_review_audits_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: document_review_audits_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.document_review_audits_id_seq OWNED BY public.document_review_audits.id;


--
-- Name: document_review_comments; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.document_review_comments (
    id bigint NOT NULL,
    document_id bigint NOT NULL,
    author_id bigint NOT NULL,
    parent_id bigint,
    page_number integer,
    severity character varying(20) DEFAULT 'comment'::character varying NOT NULL,
    comment text NOT NULL,
    resolved_by bigint,
    resolved_at timestamp(0) with time zone,
    created_at timestamp(0) with time zone,
    updated_at timestamp(0) with time zone
);


--
-- Name: document_review_comments_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.document_review_comments_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: document_review_comments_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.document_review_comments_id_seq OWNED BY public.document_review_comments.id;


--
-- Name: document_reviews; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.document_reviews (
    id bigint NOT NULL,
    document_id bigint NOT NULL,
    reviewer_id bigint NOT NULL,
    decision character varying(32) NOT NULL,
    review_notes text,
    reviewed_at timestamp(0) with time zone NOT NULL,
    created_at timestamp(0) with time zone,
    updated_at timestamp(0) with time zone,
    supersedes_review_id bigint,
    is_superseded boolean DEFAULT false NOT NULL,
    correction_reason text
);


--
-- Name: document_reviews_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.document_reviews_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: document_reviews_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.document_reviews_id_seq OWNED BY public.document_reviews.id;


--
-- Name: document_upload_audits; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.document_upload_audits (
    id bigint NOT NULL,
    document_id bigint,
    user_id bigint,
    original_filename character varying(255),
    ip_address character varying(45) NOT NULL,
    attempted_at timestamp(0) with time zone NOT NULL,
    upload_status character varying(16) NOT NULL,
    failure_reason character varying(500),
    created_at timestamp(0) with time zone,
    updated_at timestamp(0) with time zone,
    research_class_group_id bigint
);


--
-- Name: document_upload_audits_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.document_upload_audits_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: document_upload_audits_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.document_upload_audits_id_seq OWNED BY public.document_upload_audits.id;


--
-- Name: documents; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.documents (
    id bigint NOT NULL,
    user_id bigint NOT NULL,
    submission_token uuid NOT NULL,
    original_filename character varying(255) NOT NULL,
    stored_filename character varying(255) NOT NULL,
    file_type character varying(10) NOT NULL,
    mime_type character varying(150) NOT NULL,
    file_size bigint NOT NULL,
    storage_disk character varying(50) NOT NULL,
    storage_path text NOT NULL,
    content_sha256 character(64) NOT NULL,
    submitted_at timestamp(0) with time zone NOT NULL,
    status character varying(32) DEFAULT 'pending'::character varying NOT NULL,
    created_at timestamp(0) with time zone,
    updated_at timestamp(0) with time zone,
    revision_request_id bigint,
    research_class_group_id bigint,
    version_number integer DEFAULT 1 NOT NULL,
    is_current boolean DEFAULT true NOT NULL,
    document_stage character varying(32)
);


--
-- Name: documents_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.documents_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: documents_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.documents_id_seq OWNED BY public.documents.id;


--
-- Name: faculty_profile_departments; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.faculty_profile_departments (
    id bigint NOT NULL,
    faculty_profile_id bigint NOT NULL,
    department_id bigint NOT NULL,
    created_at timestamp(0) with time zone,
    updated_at timestamp(0) with time zone
);


--
-- Name: faculty_profile_departments_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.faculty_profile_departments_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: faculty_profile_departments_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.faculty_profile_departments_id_seq OWNED BY public.faculty_profile_departments.id;


--
-- Name: faculty_profiles; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.faculty_profiles (
    id bigint NOT NULL,
    user_id bigint NOT NULL,
    department_id bigint NOT NULL,
    employee_number character varying(50) NOT NULL,
    academic_rank character varying(100),
    specialization text,
    contact_number character varying(30),
    created_at timestamp(0) with time zone,
    updated_at timestamp(0) with time zone
);


--
-- Name: faculty_profiles_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.faculty_profiles_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: faculty_profiles_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.faculty_profiles_id_seq OWNED BY public.faculty_profiles.id;


--
-- Name: failed_jobs; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.failed_jobs (
    id bigint NOT NULL,
    uuid character varying(255) NOT NULL,
    connection character varying(255) NOT NULL,
    queue character varying(255) NOT NULL,
    payload text NOT NULL,
    exception text NOT NULL,
    failed_at timestamp(0) without time zone DEFAULT CURRENT_TIMESTAMP NOT NULL
);


--
-- Name: failed_jobs_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.failed_jobs_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: failed_jobs_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.failed_jobs_id_seq OWNED BY public.failed_jobs.id;


--
-- Name: job_batches; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.job_batches (
    id character varying(255) NOT NULL,
    name character varying(255) NOT NULL,
    total_jobs integer NOT NULL,
    pending_jobs integer NOT NULL,
    failed_jobs integer NOT NULL,
    failed_job_ids text NOT NULL,
    options text,
    cancelled_at integer,
    created_at integer NOT NULL,
    finished_at integer
);


--
-- Name: jobs; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.jobs (
    id bigint NOT NULL,
    queue character varying(255) NOT NULL,
    payload text NOT NULL,
    attempts smallint NOT NULL,
    reserved_at integer,
    available_at integer NOT NULL,
    created_at integer NOT NULL
);


--
-- Name: jobs_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.jobs_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: jobs_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.jobs_id_seq OWNED BY public.jobs.id;


--
-- Name: migrations; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.migrations (
    id integer NOT NULL,
    migration character varying(255) NOT NULL,
    batch integer NOT NULL
);


--
-- Name: migrations_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.migrations_id_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: migrations_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.migrations_id_seq OWNED BY public.migrations.id;


--
-- Name: milestone_definitions; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.milestone_definitions (
    id bigint NOT NULL,
    code character varying(80) NOT NULL,
    name character varying(150) NOT NULL,
    description text,
    sequence smallint NOT NULL,
    weight numeric(8,4) DEFAULT '1'::numeric NOT NULL,
    is_active boolean DEFAULT true NOT NULL,
    created_at timestamp(0) with time zone,
    updated_at timestamp(0) with time zone
);


--
-- Name: milestone_definitions_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.milestone_definitions_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: milestone_definitions_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.milestone_definitions_id_seq OWNED BY public.milestone_definitions.id;


--
-- Name: milestone_evidences; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.milestone_evidences (
    id bigint NOT NULL,
    research_group_milestone_id bigint NOT NULL,
    evidence_type character varying(40) NOT NULL,
    evidence_id bigint NOT NULL,
    linked_by bigint,
    summary character varying(500),
    linked_at timestamp(0) with time zone NOT NULL,
    created_at timestamp(0) with time zone,
    updated_at timestamp(0) with time zone
);


--
-- Name: milestone_evidences_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.milestone_evidences_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: milestone_evidences_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.milestone_evidences_id_seq OWNED BY public.milestone_evidences.id;


--
-- Name: model_has_permissions; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.model_has_permissions (
    permission_id bigint NOT NULL,
    model_type character varying(255) NOT NULL,
    model_id bigint NOT NULL
);


--
-- Name: model_has_roles; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.model_has_roles (
    role_id bigint NOT NULL,
    model_type character varying(255) NOT NULL,
    model_id bigint NOT NULL
);


--
-- Name: notifications; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.notifications (
    id uuid NOT NULL,
    type character varying(255) NOT NULL,
    notifiable_type character varying(255) NOT NULL,
    notifiable_id bigint NOT NULL,
    data json NOT NULL,
    read_at timestamp(0) with time zone,
    created_at timestamp(0) with time zone,
    updated_at timestamp(0) with time zone
);


--
-- Name: official_form_actor_assignments; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.official_form_actor_assignments (
    id bigint NOT NULL,
    official_form_instance_id bigint NOT NULL,
    user_id bigint NOT NULL,
    actor_type character varying(64) NOT NULL,
    assigned_by bigint,
    assigned_at timestamp(0) without time zone NOT NULL,
    status character varying(32) DEFAULT 'active'::character varying NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


--
-- Name: official_form_actor_assignments_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.official_form_actor_assignments_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: official_form_actor_assignments_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.official_form_actor_assignments_id_seq OWNED BY public.official_form_actor_assignments.id;


--
-- Name: official_form_definitions; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.official_form_definitions (
    id bigint NOT NULL,
    code character varying(32) NOT NULL,
    title character varying(255) NOT NULL,
    description text,
    default_category character varying(64) NOT NULL,
    ownership_scope character varying(32) DEFAULT 'research_group'::character varying NOT NULL,
    cardinality character varying(32) DEFAULT 'single_per_group'::character varying NOT NULL,
    template_view character varying(150) NOT NULL,
    is_active boolean DEFAULT true NOT NULL,
    sort_order smallint DEFAULT '0'::smallint NOT NULL,
    metadata json,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


--
-- Name: official_form_definitions_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.official_form_definitions_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: official_form_definitions_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.official_form_definitions_id_seq OWNED BY public.official_form_definitions.id;


--
-- Name: official_form_instances; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.official_form_instances (
    id bigint NOT NULL,
    official_form_definition_id bigint NOT NULL,
    research_class_group_id bigint,
    research_class_id bigint,
    context_key character varying(64) DEFAULT 'general'::character varying NOT NULL,
    source_type character varying(255),
    source_id bigint,
    initiated_by bigint NOT NULL,
    status character varying(32) DEFAULT 'draft'::character varying NOT NULL,
    current_version_id bigint,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    defense_evaluation_id bigint
);


--
-- Name: official_form_instances_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.official_form_instances_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: official_form_instances_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.official_form_instances_id_seq OWNED BY public.official_form_instances.id;


--
-- Name: official_form_signature_verifications; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.official_form_signature_verifications (
    id bigint NOT NULL,
    official_form_signature_id bigint NOT NULL,
    public_reference character(36) NOT NULL,
    created_at timestamp(0) with time zone,
    updated_at timestamp(0) with time zone
);


--
-- Name: official_form_signature_verifications_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.official_form_signature_verifications_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: official_form_signature_verifications_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.official_form_signature_verifications_id_seq OWNED BY public.official_form_signature_verifications.id;


--
-- Name: official_form_signatures; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.official_form_signatures (
    id bigint NOT NULL,
    official_form_instance_id bigint NOT NULL,
    official_form_version_id bigint NOT NULL,
    signer_user_id bigint NOT NULL,
    user_signature_id bigint,
    actor_type character varying(64) NOT NULL,
    academic_action character varying(64) NOT NULL,
    signer_name_snapshot character varying(255) NOT NULL,
    signer_email_snapshot character varying(255) NOT NULL,
    signature_storage_disk character varying(50) DEFAULT 'local'::character varying NOT NULL,
    signature_storage_path text NOT NULL,
    signature_sha256 character(64) NOT NULL,
    version_payload_sha256 character(64) NOT NULL,
    attestation_hash character(64) NOT NULL,
    attestation_key_version character varying(16) DEFAULT 'v1'::character varying NOT NULL,
    signed_at timestamp(0) with time zone NOT NULL,
    ip_address character varying(45),
    user_agent text,
    created_at timestamp(0) with time zone,
    updated_at timestamp(0) with time zone
);


--
-- Name: official_form_signatures_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.official_form_signatures_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: official_form_signatures_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.official_form_signatures_id_seq OWNED BY public.official_form_signatures.id;


--
-- Name: official_form_verifications; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.official_form_verifications (
    id bigint NOT NULL,
    official_form_version_id bigint NOT NULL,
    public_reference character(36) NOT NULL,
    created_at timestamp(0) with time zone,
    updated_at timestamp(0) with time zone
);


--
-- Name: official_form_verifications_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.official_form_verifications_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: official_form_verifications_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.official_form_verifications_id_seq OWNED BY public.official_form_verifications.id;


--
-- Name: official_form_versions; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.official_form_versions (
    id bigint NOT NULL,
    official_form_instance_id bigint NOT NULL,
    version_number integer NOT NULL,
    payload json NOT NULL,
    created_by bigint NOT NULL,
    supersedes_version_id bigint,
    is_current boolean DEFAULT true NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    source_snapshot json
);


--
-- Name: official_form_versions_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.official_form_versions_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: official_form_versions_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.official_form_versions_id_seq OWNED BY public.official_form_versions.id;


--
-- Name: password_reset_tokens; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.password_reset_tokens (
    email character varying(255) NOT NULL,
    token character varying(255) NOT NULL,
    created_at timestamp(0) without time zone
);


--
-- Name: permissions; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.permissions (
    id bigint NOT NULL,
    name character varying(255) NOT NULL,
    guard_name character varying(255) NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    display_name character varying(255),
    description text,
    module character varying(255),
    scope character varying(255)
);


--
-- Name: permissions_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.permissions_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: permissions_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.permissions_id_seq OWNED BY public.permissions.id;


--
-- Name: programs; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.programs (
    id bigint NOT NULL,
    department_id bigint NOT NULL,
    code character varying(30) NOT NULL,
    name character varying(255) NOT NULL,
    degree_level character varying(50),
    is_active boolean DEFAULT true NOT NULL,
    created_at timestamp(0) with time zone,
    updated_at timestamp(0) with time zone
);


--
-- Name: programs_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.programs_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: programs_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.programs_id_seq OWNED BY public.programs.id;


--
-- Name: research_class_actor_assignments; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.research_class_actor_assignments (
    id bigint NOT NULL,
    research_class_id bigint NOT NULL,
    user_id bigint NOT NULL,
    actor_type character varying(64) NOT NULL,
    assigned_by bigint,
    assigned_at timestamp(0) without time zone NOT NULL,
    status character varying(32) DEFAULT 'active'::character varying NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


--
-- Name: research_class_actor_assignments_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.research_class_actor_assignments_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: research_class_actor_assignments_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.research_class_actor_assignments_id_seq OWNED BY public.research_class_actor_assignments.id;


--
-- Name: research_class_enrollments; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.research_class_enrollments (
    id bigint NOT NULL,
    research_class_id bigint NOT NULL,
    student_id bigint NOT NULL,
    status character varying(20) DEFAULT 'pending'::character varying NOT NULL,
    joined_at timestamp(0) with time zone,
    created_at timestamp(0) with time zone,
    updated_at timestamp(0) with time zone,
    requested_at timestamp(0) with time zone,
    reviewed_by bigint,
    reviewed_at timestamp(0) with time zone
);


--
-- Name: research_class_enrollments_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.research_class_enrollments_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: research_class_enrollments_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.research_class_enrollments_id_seq OWNED BY public.research_class_enrollments.id;


--
-- Name: research_class_group_adviser_histories; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.research_class_group_adviser_histories (
    id bigint NOT NULL,
    research_class_group_id bigint NOT NULL,
    adviser_id bigint NOT NULL,
    assigned_by bigint NOT NULL,
    assigned_at timestamp(0) with time zone NOT NULL,
    ended_at timestamp(0) with time zone,
    ended_by bigint,
    created_at timestamp(0) with time zone,
    updated_at timestamp(0) with time zone
);


--
-- Name: research_class_group_adviser_histories_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.research_class_group_adviser_histories_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: research_class_group_adviser_histories_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.research_class_group_adviser_histories_id_seq OWNED BY public.research_class_group_adviser_histories.id;


--
-- Name: research_class_group_adviser_requests; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.research_class_group_adviser_requests (
    id bigint NOT NULL,
    research_class_group_id bigint NOT NULL,
    adviser_id bigint NOT NULL,
    requested_by bigint NOT NULL,
    status character varying(20) DEFAULT 'pending'::character varying NOT NULL,
    requested_at timestamp(0) with time zone NOT NULL,
    responded_at timestamp(0) with time zone,
    cancelled_at timestamp(0) with time zone,
    created_at timestamp(0) with time zone,
    updated_at timestamp(0) with time zone
);


--
-- Name: research_class_group_adviser_requests_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.research_class_group_adviser_requests_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: research_class_group_adviser_requests_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.research_class_group_adviser_requests_id_seq OWNED BY public.research_class_group_adviser_requests.id;


--
-- Name: research_class_group_member_histories; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.research_class_group_member_histories (
    id bigint NOT NULL,
    research_class_group_id bigint NOT NULL,
    research_class_id bigint NOT NULL,
    research_class_enrollment_id bigint,
    student_id bigint NOT NULL,
    assigned_by bigint,
    joined_at timestamp(0) with time zone,
    archived_at timestamp(0) with time zone NOT NULL,
    archive_reason character varying(32) DEFAULT 'group_disbanded'::character varying NOT NULL,
    created_at timestamp(0) with time zone,
    updated_at timestamp(0) with time zone
);


--
-- Name: research_class_group_member_histories_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.research_class_group_member_histories_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: research_class_group_member_histories_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.research_class_group_member_histories_id_seq OWNED BY public.research_class_group_member_histories.id;


--
-- Name: research_class_group_members; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.research_class_group_members (
    id bigint NOT NULL,
    research_class_group_id bigint NOT NULL,
    research_class_id bigint NOT NULL,
    research_class_enrollment_id bigint NOT NULL,
    student_id bigint NOT NULL,
    assigned_by bigint NOT NULL,
    created_at timestamp(0) with time zone,
    updated_at timestamp(0) with time zone
);


--
-- Name: research_class_group_members_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.research_class_group_members_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: research_class_group_members_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.research_class_group_members_id_seq OWNED BY public.research_class_group_members.id;


--
-- Name: research_class_groups; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.research_class_groups (
    id bigint NOT NULL,
    research_class_id bigint NOT NULL,
    research_group_id bigint,
    creation_token uuid NOT NULL,
    name character varying(120) NOT NULL,
    adviser_id bigint,
    created_by bigint NOT NULL,
    created_at timestamp(0) with time zone,
    updated_at timestamp(0) with time zone,
    status character varying(20) DEFAULT 'active'::character varying NOT NULL,
    disbanded_at timestamp(0) with time zone,
    leader_student_id bigint
);


--
-- Name: research_class_groups_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.research_class_groups_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: research_class_groups_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.research_class_groups_id_seq OWNED BY public.research_class_groups.id;


--
-- Name: research_class_panel_committees; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.research_class_panel_committees (
    id bigint NOT NULL,
    research_class_id bigint NOT NULL,
    defense_type character varying(50) NOT NULL,
    chairperson_id bigint NOT NULL,
    created_by bigint,
    updated_by bigint,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


--
-- Name: research_class_panel_committees_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.research_class_panel_committees_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: research_class_panel_committees_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.research_class_panel_committees_id_seq OWNED BY public.research_class_panel_committees.id;


--
-- Name: research_class_panel_members; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.research_class_panel_members (
    id bigint NOT NULL,
    committee_id bigint NOT NULL,
    user_id bigint NOT NULL,
    panel_position character varying(20) NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


--
-- Name: research_class_panel_members_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.research_class_panel_members_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: research_class_panel_members_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.research_class_panel_members_id_seq OWNED BY public.research_class_panel_members.id;


--
-- Name: research_classes; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.research_classes (
    id bigint NOT NULL,
    facilitator_id bigint NOT NULL,
    creation_token uuid NOT NULL,
    name character varying(120) NOT NULL,
    description text,
    join_code_hash character(64) NOT NULL,
    join_code_encrypted text NOT NULL,
    max_students smallint DEFAULT '50'::smallint NOT NULL,
    is_active boolean DEFAULT true NOT NULL,
    created_at timestamp(0) with time zone,
    updated_at timestamp(0) with time zone
);


--
-- Name: research_classes_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.research_classes_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: research_classes_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.research_classes_id_seq OWNED BY public.research_classes.id;


--
-- Name: research_group_adviser_change_requests; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.research_group_adviser_change_requests (
    id bigint NOT NULL,
    official_form_instance_id bigint NOT NULL,
    research_class_group_id bigint NOT NULL,
    previous_adviser_id bigint NOT NULL,
    requested_adviser_id bigint NOT NULL,
    requested_by bigint NOT NULL,
    reason text NOT NULL,
    supporting_explanation text,
    supporting_document_disk character varying(50),
    supporting_document_path character varying(500),
    supporting_document_original_name character varying(255),
    supporting_document_mime_type character varying(100),
    supporting_document_size bigint,
    supporting_document_sha256 character(64),
    status character varying(30) DEFAULT 'submitted'::character varying NOT NULL,
    leader_confirmed_at timestamp(0) with time zone NOT NULL,
    reviewed_by bigint,
    reviewer_remarks text,
    reviewed_at timestamp(0) with time zone,
    effective_at timestamp(0) with time zone,
    created_at timestamp(0) with time zone,
    updated_at timestamp(0) with time zone,
    CONSTRAINT adviser_change_distinct_advisers_check CHECK ((previous_adviser_id <> requested_adviser_id)),
    CONSTRAINT adviser_change_status_check CHECK (((status)::text = ANY ((ARRAY['submitted'::character varying, 'approved'::character varying, 'rejected'::character varying, 'cancelled'::character varying])::text[])))
);


--
-- Name: research_group_adviser_change_requests_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.research_group_adviser_change_requests_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: research_group_adviser_change_requests_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.research_group_adviser_change_requests_id_seq OWNED BY public.research_group_adviser_change_requests.id;


--
-- Name: research_group_members; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.research_group_members (
    id bigint NOT NULL,
    research_group_id bigint NOT NULL,
    student_profile_id bigint NOT NULL,
    member_role character varying(30) DEFAULT 'member'::character varying NOT NULL,
    joined_at timestamp(0) with time zone DEFAULT CURRENT_TIMESTAMP NOT NULL,
    left_at timestamp(0) with time zone,
    created_at timestamp(0) with time zone,
    updated_at timestamp(0) with time zone
);


--
-- Name: research_group_members_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.research_group_members_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: research_group_members_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.research_group_members_id_seq OWNED BY public.research_group_members.id;


--
-- Name: research_group_milestone_events; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.research_group_milestone_events (
    id bigint NOT NULL,
    research_group_milestone_id bigint NOT NULL,
    actor_id bigint,
    event character varying(48) NOT NULL,
    from_status character varying(32),
    to_status character varying(32),
    reason text,
    old_values json,
    new_values json,
    override_order boolean DEFAULT false NOT NULL,
    ip_address character varying(45),
    occurred_at timestamp(0) with time zone NOT NULL,
    created_at timestamp(0) with time zone
);


--
-- Name: research_group_milestone_events_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.research_group_milestone_events_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: research_group_milestone_events_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.research_group_milestone_events_id_seq OWNED BY public.research_group_milestone_events.id;


--
-- Name: research_group_milestones; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.research_group_milestones (
    id bigint NOT NULL,
    research_class_group_id bigint NOT NULL,
    milestone_definition_id bigint NOT NULL,
    status character varying(32) DEFAULT 'pending'::character varying NOT NULL,
    due_at timestamp(0) with time zone,
    started_at timestamp(0) with time zone,
    started_by bigint,
    completed_at timestamp(0) with time zone,
    completed_by bigint,
    not_applicable_reason text,
    remarks text,
    updated_by bigint,
    created_at timestamp(0) with time zone,
    updated_at timestamp(0) with time zone
);


--
-- Name: research_group_milestones_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.research_group_milestones_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: research_group_milestones_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.research_group_milestones_id_seq OWNED BY public.research_group_milestones.id;


--
-- Name: research_group_panel_committees; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.research_group_panel_committees (
    id bigint NOT NULL,
    research_class_group_id bigint NOT NULL,
    defense_type character varying(50) NOT NULL,
    chairperson_id bigint NOT NULL,
    is_custom boolean DEFAULT false NOT NULL,
    created_by bigint,
    updated_by bigint,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


--
-- Name: research_group_panel_committees_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.research_group_panel_committees_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: research_group_panel_committees_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.research_group_panel_committees_id_seq OWNED BY public.research_group_panel_committees.id;


--
-- Name: research_group_panel_members; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.research_group_panel_members (
    id bigint NOT NULL,
    committee_id bigint NOT NULL,
    user_id bigint NOT NULL,
    panel_position character varying(20) NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


--
-- Name: research_group_panel_members_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.research_group_panel_members_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: research_group_panel_members_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.research_group_panel_members_id_seq OWNED BY public.research_group_panel_members.id;


--
-- Name: research_groups; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.research_groups (
    id bigint NOT NULL,
    program_id bigint NOT NULL,
    academic_term_id bigint NOT NULL,
    name character varying(255) NOT NULL,
    created_by bigint,
    created_at timestamp(0) with time zone,
    updated_at timestamp(0) with time zone
);


--
-- Name: research_groups_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.research_groups_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: research_groups_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.research_groups_id_seq OWNED BY public.research_groups.id;


--
-- Name: research_project_title_histories; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.research_project_title_histories (
    id bigint NOT NULL,
    research_project_id bigint NOT NULL,
    previous_title character varying(500) NOT NULL,
    revised_title character varying(500) NOT NULL,
    reason text NOT NULL,
    changed_by bigint NOT NULL,
    effective_at timestamp(0) with time zone NOT NULL,
    created_at timestamp(0) with time zone,
    updated_at timestamp(0) with time zone
);


--
-- Name: research_project_title_histories_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.research_project_title_histories_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: research_project_title_histories_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.research_project_title_histories_id_seq OWNED BY public.research_project_title_histories.id;


--
-- Name: research_projects; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.research_projects (
    id bigint NOT NULL,
    research_group_id bigint NOT NULL,
    title character varying(500) NOT NULL,
    abstract text,
    keywords json DEFAULT '[]'::json NOT NULL,
    category character varying(150),
    status character varying(50) DEFAULT 'draft'::character varying NOT NULL,
    created_by bigint,
    approved_at timestamp(0) with time zone,
    completed_at timestamp(0) with time zone,
    archived_at timestamp(0) with time zone,
    created_at timestamp(0) with time zone,
    updated_at timestamp(0) with time zone
);


--
-- Name: research_projects_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.research_projects_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: research_projects_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.research_projects_id_seq OWNED BY public.research_projects.id;


--
-- Name: research_proposals; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.research_proposals (
    id bigint NOT NULL,
    research_project_id bigint,
    document_id bigint,
    submitted_by bigint NOT NULL,
    reviewed_by bigint,
    version integer DEFAULT 1 NOT NULL,
    title character varying(255) NOT NULL,
    status character varying(32) DEFAULT 'pending'::character varying NOT NULL,
    review_notes text,
    submitted_at timestamp(0) with time zone,
    reviewed_at timestamp(0) with time zone,
    created_at timestamp(0) with time zone,
    updated_at timestamp(0) with time zone
);


--
-- Name: research_proposals_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.research_proposals_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: research_proposals_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.research_proposals_id_seq OWNED BY public.research_proposals.id;


--
-- Name: revision_request_events; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.revision_request_events (
    id bigint NOT NULL,
    revision_request_id bigint NOT NULL,
    actor_id bigint NOT NULL,
    document_id bigint,
    action character varying(32) NOT NULL,
    from_status character varying(32),
    to_status character varying(32) NOT NULL,
    notes text,
    ip_address character varying(45),
    metadata json,
    occurred_at timestamp(0) with time zone NOT NULL,
    created_at timestamp(0) with time zone,
    updated_at timestamp(0) with time zone
);


--
-- Name: revision_request_events_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.revision_request_events_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: revision_request_events_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.revision_request_events_id_seq OWNED BY public.revision_request_events.id;


--
-- Name: revision_requests; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.revision_requests (
    id bigint NOT NULL,
    research_project_id bigint,
    document_id bigint,
    requested_by bigint NOT NULL,
    assigned_to bigint,
    title character varying(255) NOT NULL,
    instructions text NOT NULL,
    status character varying(32) DEFAULT 'open'::character varying NOT NULL,
    due_at timestamp(0) with time zone,
    resolved_at timestamp(0) with time zone,
    created_at timestamp(0) with time zone,
    updated_at timestamp(0) with time zone,
    research_class_group_id bigint,
    source_document_review_id bigint,
    submitted_document_id bigint,
    source_type character varying(32) DEFAULT 'document_review'::character varying NOT NULL,
    invalidated_at timestamp(0) with time zone,
    invalidated_reason text
);


--
-- Name: revision_requests_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.revision_requests_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: revision_requests_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.revision_requests_id_seq OWNED BY public.revision_requests.id;


--
-- Name: role_has_permissions; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.role_has_permissions (
    permission_id bigint NOT NULL,
    role_id bigint NOT NULL
);


--
-- Name: roles; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.roles (
    id bigint NOT NULL,
    name character varying(255) NOT NULL,
    guard_name character varying(255) NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    display_name character varying(255),
    description text,
    is_system boolean DEFAULT false NOT NULL,
    is_assignable boolean DEFAULT true NOT NULL
);


--
-- Name: roles_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.roles_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: roles_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.roles_id_seq OWNED BY public.roles.id;


--
-- Name: sessions; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.sessions (
    id character varying(255) NOT NULL,
    user_id bigint,
    ip_address character varying(45),
    user_agent text,
    payload text NOT NULL,
    last_activity integer NOT NULL
);


--
-- Name: signature_audits; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.signature_audits (
    id bigint NOT NULL,
    user_signature_id bigint,
    user_id bigint,
    action character varying(30) NOT NULL,
    ip_address character varying(45),
    user_agent character varying(1000),
    occurred_at timestamp(0) with time zone NOT NULL,
    created_at timestamp(0) with time zone,
    updated_at timestamp(0) with time zone
);


--
-- Name: signature_audits_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.signature_audits_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: signature_audits_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.signature_audits_id_seq OWNED BY public.signature_audits.id;


--
-- Name: student_profiles; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.student_profiles (
    id bigint NOT NULL,
    user_id bigint NOT NULL,
    program_id bigint NOT NULL,
    student_number character varying(50) NOT NULL,
    year_level smallint,
    contact_number character varying(30),
    created_at timestamp(0) with time zone,
    updated_at timestamp(0) with time zone
);


--
-- Name: student_profiles_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.student_profiles_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: student_profiles_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.student_profiles_id_seq OWNED BY public.student_profiles.id;


--
-- Name: system_backup_settings; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.system_backup_settings (
    id bigint NOT NULL,
    enabled boolean DEFAULT false NOT NULL,
    frequency character varying(16) DEFAULT 'daily'::character varying NOT NULL,
    run_time time(0) without time zone DEFAULT '23:00:00'::time without time zone NOT NULL,
    retention_count smallint DEFAULT '14'::smallint NOT NULL,
    last_scheduled_for timestamp(0) with time zone,
    updated_by bigint,
    created_at timestamp(0) with time zone,
    updated_at timestamp(0) with time zone,
    max_import_mb smallint DEFAULT '10'::smallint NOT NULL
);


--
-- Name: system_backup_settings_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.system_backup_settings_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: system_backup_settings_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.system_backup_settings_id_seq OWNED BY public.system_backup_settings.id;


--
-- Name: system_backups; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.system_backups (
    id bigint NOT NULL,
    filename character varying(255) NOT NULL,
    storage_disk character varying(40) DEFAULT 'local'::character varying NOT NULL,
    storage_path character varying(1000),
    status character varying(20) DEFAULT 'running'::character varying NOT NULL,
    trigger character varying(20) DEFAULT 'manual'::character varying NOT NULL,
    size_bytes bigint,
    sha256 character varying(64),
    failure_message text,
    triggered_by bigint,
    started_at timestamp(0) with time zone NOT NULL,
    completed_at timestamp(0) with time zone,
    created_at timestamp(0) with time zone,
    updated_at timestamp(0) with time zone,
    verification_status character varying(20),
    verification_message text,
    verified_at timestamp(0) with time zone,
    verified_by bigint,
    manifest json,
    restore_status character varying(20),
    restore_message text,
    restored_at timestamp(0) with time zone,
    restored_by bigint
);


--
-- Name: system_backups_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.system_backups_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: system_backups_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.system_backups_id_seq OWNED BY public.system_backups.id;


--
-- Name: system_settings; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.system_settings (
    id bigint NOT NULL,
    system_name character varying(255) DEFAULT 'NDMU Research Management and Assistance System'::character varying NOT NULL,
    support_email character varying(255) DEFAULT 'research@ndmu.edu.ph'::character varying NOT NULL,
    student_registration_enabled boolean DEFAULT true NOT NULL,
    email_notifications_enabled boolean DEFAULT true NOT NULL,
    maintenance_notice character varying(500),
    updated_by bigint,
    created_at timestamp(0) with time zone,
    updated_at timestamp(0) with time zone,
    turnstile_enabled boolean DEFAULT true NOT NULL,
    defense_high_traffic_mode_enabled boolean DEFAULT false NOT NULL,
    document_max_upload_mb smallint DEFAULT '10'::smallint NOT NULL,
    maintenance_services json
);


--
-- Name: system_settings_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.system_settings_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: system_settings_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.system_settings_id_seq OWNED BY public.system_settings.id;


--
-- Name: title_presentations; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.title_presentations (
    id bigint NOT NULL,
    defense_id bigint NOT NULL,
    official_form_instance_id bigint NOT NULL,
    official_form_version_id bigint NOT NULL,
    status character varying(40) DEFAULT 'scheduled'::character varying NOT NULL,
    approved_title_number smallint,
    remarks text,
    result_recorded_by bigint,
    result_recorded_at timestamp(0) without time zone,
    presented_at timestamp(0) without time zone,
    presentation_completed_by bigint,
    finalized_at timestamp(0) without time zone,
    finalized_by bigint,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    CONSTRAINT title_presentations_approved_number_check CHECK (((approved_title_number IS NULL) OR ((approved_title_number >= 1) AND (approved_title_number <= 3))))
);


--
-- Name: title_presentations_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.title_presentations_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: title_presentations_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.title_presentations_id_seq OWNED BY public.title_presentations.id;


--
-- Name: user_onboarding_completions; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.user_onboarding_completions (
    id bigint NOT NULL,
    user_id bigint NOT NULL,
    workspace character varying(32) NOT NULL,
    status character varying(16) NOT NULL,
    completed_at timestamp(0) without time zone NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


--
-- Name: user_onboarding_completions_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.user_onboarding_completions_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: user_onboarding_completions_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.user_onboarding_completions_id_seq OWNED BY public.user_onboarding_completions.id;


--
-- Name: user_signatures; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.user_signatures (
    id bigint NOT NULL,
    user_id bigint NOT NULL,
    storage_disk character varying(50) NOT NULL,
    storage_path text NOT NULL,
    original_filename character varying(255) NOT NULL,
    mime_type character varying(50) NOT NULL,
    file_size bigint NOT NULL,
    content_sha256 character(64) NOT NULL,
    registered_at timestamp(0) with time zone NOT NULL,
    created_at timestamp(0) with time zone,
    updated_at timestamp(0) with time zone
);


--
-- Name: user_signatures_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.user_signatures_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: user_signatures_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.user_signatures_id_seq OWNED BY public.user_signatures.id;


--
-- Name: users; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.users (
    id bigint NOT NULL,
    first_name character varying(100),
    middle_name character varying(100),
    last_name character varying(100),
    suffix character varying(20),
    name character varying(255) NOT NULL,
    email character varying(255) NOT NULL,
    email_verified_at timestamp(0) without time zone,
    password character varying(255) NOT NULL,
    status character varying(255) DEFAULT 'pending'::character varying NOT NULL,
    approved_at timestamp(0) without time zone,
    remember_token character varying(100),
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    student_id character varying(255),
    program character varying(255),
    year_level character varying(255),
    department character varying(255),
    user_type character varying(20) DEFAULT 'faculty'::character varying NOT NULL,
    profile_photo_disk character varying(32),
    profile_photo_path character varying(512),
    profile_photo_mime_type character varying(100),
    profile_photo_size bigint,
    profile_photo_updated_at timestamp with time zone,
    must_change_password boolean DEFAULT false NOT NULL,
    temporary_password_expires_at timestamp(0) with time zone,
    password_changed_at timestamp(0) with time zone
);


--
-- Name: users_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.users_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: users_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.users_id_seq OWNED BY public.users.id;


--
-- Name: academic_terms id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.academic_terms ALTER COLUMN id SET DEFAULT nextval('public.academic_terms_id_seq'::regclass);


--
-- Name: academic_years id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.academic_years ALTER COLUMN id SET DEFAULT nextval('public.academic_years_id_seq'::regclass);


--
-- Name: adviser_assignments id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.adviser_assignments ALTER COLUMN id SET DEFAULT nextval('public.adviser_assignments_id_seq'::regclass);


--
-- Name: audit_logs id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.audit_logs ALTER COLUMN id SET DEFAULT nextval('public.audit_logs_id_seq'::regclass);


--
-- Name: colleges id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.colleges ALTER COLUMN id SET DEFAULT nextval('public.colleges_id_seq'::regclass);


--
-- Name: consultation_attendances id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.consultation_attendances ALTER COLUMN id SET DEFAULT nextval('public.consultation_attendances_id_seq'::regclass);


--
-- Name: consultation_audits id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.consultation_audits ALTER COLUMN id SET DEFAULT nextval('public.consultation_audits_id_seq'::regclass);


--
-- Name: consultation_records id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.consultation_records ALTER COLUMN id SET DEFAULT nextval('public.consultation_records_id_seq'::regclass);


--
-- Name: consultation_requests id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.consultation_requests ALTER COLUMN id SET DEFAULT nextval('public.consultation_requests_id_seq'::regclass);


--
-- Name: consultation_schedule_proposals id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.consultation_schedule_proposals ALTER COLUMN id SET DEFAULT nextval('public.consultation_schedule_proposals_id_seq'::regclass);


--
-- Name: defense_evaluation_round_panelists id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.defense_evaluation_round_panelists ALTER COLUMN id SET DEFAULT nextval('public.defense_evaluation_round_panelists_id_seq'::regclass);


--
-- Name: defense_evaluation_round_students id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.defense_evaluation_round_students ALTER COLUMN id SET DEFAULT nextval('public.defense_evaluation_round_students_id_seq'::regclass);


--
-- Name: defense_evaluation_rounds id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.defense_evaluation_rounds ALTER COLUMN id SET DEFAULT nextval('public.defense_evaluation_rounds_id_seq'::regclass);


--
-- Name: defense_evaluation_student_scores id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.defense_evaluation_student_scores ALTER COLUMN id SET DEFAULT nextval('public.defense_evaluation_student_scores_id_seq'::regclass);


--
-- Name: defense_evaluation_student_summaries id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.defense_evaluation_student_summaries ALTER COLUMN id SET DEFAULT nextval('public.defense_evaluation_student_summaries_id_seq'::regclass);


--
-- Name: defense_evaluation_summaries id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.defense_evaluation_summaries ALTER COLUMN id SET DEFAULT nextval('public.defense_evaluation_summaries_id_seq'::regclass);


--
-- Name: defense_evaluations id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.defense_evaluations ALTER COLUMN id SET DEFAULT nextval('public.defense_evaluations_id_seq'::regclass);


--
-- Name: defense_panel_assignments id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.defense_panel_assignments ALTER COLUMN id SET DEFAULT nextval('public.defense_panel_assignments_id_seq'::regclass);


--
-- Name: defense_rooms id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.defense_rooms ALTER COLUMN id SET DEFAULT nextval('public.defense_rooms_id_seq'::regclass);


--
-- Name: defense_schedules id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.defense_schedules ALTER COLUMN id SET DEFAULT nextval('public.defense_schedules_id_seq'::regclass);


--
-- Name: defense_sessions id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.defense_sessions ALTER COLUMN id SET DEFAULT nextval('public.defense_sessions_id_seq'::regclass);


--
-- Name: defenses id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.defenses ALTER COLUMN id SET DEFAULT nextval('public.defenses_id_seq'::regclass);


--
-- Name: departments id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.departments ALTER COLUMN id SET DEFAULT nextval('public.departments_id_seq'::regclass);


--
-- Name: document_access_audits id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.document_access_audits ALTER COLUMN id SET DEFAULT nextval('public.document_access_audits_id_seq'::regclass);


--
-- Name: document_review_audits id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.document_review_audits ALTER COLUMN id SET DEFAULT nextval('public.document_review_audits_id_seq'::regclass);


--
-- Name: document_review_comments id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.document_review_comments ALTER COLUMN id SET DEFAULT nextval('public.document_review_comments_id_seq'::regclass);


--
-- Name: document_reviews id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.document_reviews ALTER COLUMN id SET DEFAULT nextval('public.document_reviews_id_seq'::regclass);


--
-- Name: document_upload_audits id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.document_upload_audits ALTER COLUMN id SET DEFAULT nextval('public.document_upload_audits_id_seq'::regclass);


--
-- Name: documents id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.documents ALTER COLUMN id SET DEFAULT nextval('public.documents_id_seq'::regclass);


--
-- Name: faculty_profile_departments id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.faculty_profile_departments ALTER COLUMN id SET DEFAULT nextval('public.faculty_profile_departments_id_seq'::regclass);


--
-- Name: faculty_profiles id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.faculty_profiles ALTER COLUMN id SET DEFAULT nextval('public.faculty_profiles_id_seq'::regclass);


--
-- Name: failed_jobs id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.failed_jobs ALTER COLUMN id SET DEFAULT nextval('public.failed_jobs_id_seq'::regclass);


--
-- Name: jobs id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.jobs ALTER COLUMN id SET DEFAULT nextval('public.jobs_id_seq'::regclass);


--
-- Name: migrations id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.migrations ALTER COLUMN id SET DEFAULT nextval('public.migrations_id_seq'::regclass);


--
-- Name: milestone_definitions id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.milestone_definitions ALTER COLUMN id SET DEFAULT nextval('public.milestone_definitions_id_seq'::regclass);


--
-- Name: milestone_evidences id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.milestone_evidences ALTER COLUMN id SET DEFAULT nextval('public.milestone_evidences_id_seq'::regclass);


--
-- Name: official_form_actor_assignments id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.official_form_actor_assignments ALTER COLUMN id SET DEFAULT nextval('public.official_form_actor_assignments_id_seq'::regclass);


--
-- Name: official_form_definitions id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.official_form_definitions ALTER COLUMN id SET DEFAULT nextval('public.official_form_definitions_id_seq'::regclass);


--
-- Name: official_form_instances id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.official_form_instances ALTER COLUMN id SET DEFAULT nextval('public.official_form_instances_id_seq'::regclass);


--
-- Name: official_form_signature_verifications id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.official_form_signature_verifications ALTER COLUMN id SET DEFAULT nextval('public.official_form_signature_verifications_id_seq'::regclass);


--
-- Name: official_form_signatures id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.official_form_signatures ALTER COLUMN id SET DEFAULT nextval('public.official_form_signatures_id_seq'::regclass);


--
-- Name: official_form_verifications id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.official_form_verifications ALTER COLUMN id SET DEFAULT nextval('public.official_form_verifications_id_seq'::regclass);


--
-- Name: official_form_versions id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.official_form_versions ALTER COLUMN id SET DEFAULT nextval('public.official_form_versions_id_seq'::regclass);


--
-- Name: permissions id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.permissions ALTER COLUMN id SET DEFAULT nextval('public.permissions_id_seq'::regclass);


--
-- Name: programs id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.programs ALTER COLUMN id SET DEFAULT nextval('public.programs_id_seq'::regclass);


--
-- Name: research_class_actor_assignments id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.research_class_actor_assignments ALTER COLUMN id SET DEFAULT nextval('public.research_class_actor_assignments_id_seq'::regclass);


--
-- Name: research_class_enrollments id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.research_class_enrollments ALTER COLUMN id SET DEFAULT nextval('public.research_class_enrollments_id_seq'::regclass);


--
-- Name: research_class_group_adviser_histories id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.research_class_group_adviser_histories ALTER COLUMN id SET DEFAULT nextval('public.research_class_group_adviser_histories_id_seq'::regclass);


--
-- Name: research_class_group_adviser_requests id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.research_class_group_adviser_requests ALTER COLUMN id SET DEFAULT nextval('public.research_class_group_adviser_requests_id_seq'::regclass);


--
-- Name: research_class_group_member_histories id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.research_class_group_member_histories ALTER COLUMN id SET DEFAULT nextval('public.research_class_group_member_histories_id_seq'::regclass);


--
-- Name: research_class_group_members id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.research_class_group_members ALTER COLUMN id SET DEFAULT nextval('public.research_class_group_members_id_seq'::regclass);


--
-- Name: research_class_groups id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.research_class_groups ALTER COLUMN id SET DEFAULT nextval('public.research_class_groups_id_seq'::regclass);


--
-- Name: research_class_panel_committees id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.research_class_panel_committees ALTER COLUMN id SET DEFAULT nextval('public.research_class_panel_committees_id_seq'::regclass);


--
-- Name: research_class_panel_members id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.research_class_panel_members ALTER COLUMN id SET DEFAULT nextval('public.research_class_panel_members_id_seq'::regclass);


--
-- Name: research_classes id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.research_classes ALTER COLUMN id SET DEFAULT nextval('public.research_classes_id_seq'::regclass);


--
-- Name: research_group_adviser_change_requests id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.research_group_adviser_change_requests ALTER COLUMN id SET DEFAULT nextval('public.research_group_adviser_change_requests_id_seq'::regclass);


--
-- Name: research_group_members id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.research_group_members ALTER COLUMN id SET DEFAULT nextval('public.research_group_members_id_seq'::regclass);


--
-- Name: research_group_milestone_events id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.research_group_milestone_events ALTER COLUMN id SET DEFAULT nextval('public.research_group_milestone_events_id_seq'::regclass);


--
-- Name: research_group_milestones id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.research_group_milestones ALTER COLUMN id SET DEFAULT nextval('public.research_group_milestones_id_seq'::regclass);


--
-- Name: research_group_panel_committees id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.research_group_panel_committees ALTER COLUMN id SET DEFAULT nextval('public.research_group_panel_committees_id_seq'::regclass);


--
-- Name: research_group_panel_members id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.research_group_panel_members ALTER COLUMN id SET DEFAULT nextval('public.research_group_panel_members_id_seq'::regclass);


--
-- Name: research_groups id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.research_groups ALTER COLUMN id SET DEFAULT nextval('public.research_groups_id_seq'::regclass);


--
-- Name: research_project_title_histories id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.research_project_title_histories ALTER COLUMN id SET DEFAULT nextval('public.research_project_title_histories_id_seq'::regclass);


--
-- Name: research_projects id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.research_projects ALTER COLUMN id SET DEFAULT nextval('public.research_projects_id_seq'::regclass);


--
-- Name: research_proposals id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.research_proposals ALTER COLUMN id SET DEFAULT nextval('public.research_proposals_id_seq'::regclass);


--
-- Name: revision_request_events id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.revision_request_events ALTER COLUMN id SET DEFAULT nextval('public.revision_request_events_id_seq'::regclass);


--
-- Name: revision_requests id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.revision_requests ALTER COLUMN id SET DEFAULT nextval('public.revision_requests_id_seq'::regclass);


--
-- Name: roles id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.roles ALTER COLUMN id SET DEFAULT nextval('public.roles_id_seq'::regclass);


--
-- Name: signature_audits id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.signature_audits ALTER COLUMN id SET DEFAULT nextval('public.signature_audits_id_seq'::regclass);


--
-- Name: student_profiles id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.student_profiles ALTER COLUMN id SET DEFAULT nextval('public.student_profiles_id_seq'::regclass);


--
-- Name: system_backup_settings id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.system_backup_settings ALTER COLUMN id SET DEFAULT nextval('public.system_backup_settings_id_seq'::regclass);


--
-- Name: system_backups id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.system_backups ALTER COLUMN id SET DEFAULT nextval('public.system_backups_id_seq'::regclass);


--
-- Name: system_settings id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.system_settings ALTER COLUMN id SET DEFAULT nextval('public.system_settings_id_seq'::regclass);


--
-- Name: title_presentations id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.title_presentations ALTER COLUMN id SET DEFAULT nextval('public.title_presentations_id_seq'::regclass);


--
-- Name: user_onboarding_completions id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.user_onboarding_completions ALTER COLUMN id SET DEFAULT nextval('public.user_onboarding_completions_id_seq'::regclass);


--
-- Name: user_signatures id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.user_signatures ALTER COLUMN id SET DEFAULT nextval('public.user_signatures_id_seq'::regclass);


--
-- Name: users id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.users ALTER COLUMN id SET DEFAULT nextval('public.users_id_seq'::regclass);


--
-- Name: academic_terms academic_terms_academic_year_id_name_unique; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.academic_terms
    ADD CONSTRAINT academic_terms_academic_year_id_name_unique UNIQUE (academic_year_id, name);


--
-- Name: academic_terms academic_terms_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.academic_terms
    ADD CONSTRAINT academic_terms_pkey PRIMARY KEY (id);


--
-- Name: academic_years academic_years_name_unique; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.academic_years
    ADD CONSTRAINT academic_years_name_unique UNIQUE (name);


--
-- Name: academic_years academic_years_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.academic_years
    ADD CONSTRAINT academic_years_pkey PRIMARY KEY (id);


--
-- Name: adviser_assignments adviser_assignments_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.adviser_assignments
    ADD CONSTRAINT adviser_assignments_pkey PRIMARY KEY (id);


--
-- Name: research_group_adviser_change_requests adviser_change_form_unique; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.research_group_adviser_change_requests
    ADD CONSTRAINT adviser_change_form_unique UNIQUE (official_form_instance_id);


--
-- Name: audit_logs audit_logs_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.audit_logs
    ADD CONSTRAINT audit_logs_pkey PRIMARY KEY (id);


--
-- Name: cache_locks cache_locks_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.cache_locks
    ADD CONSTRAINT cache_locks_pkey PRIMARY KEY (key);


--
-- Name: cache cache_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.cache
    ADD CONSTRAINT cache_pkey PRIMARY KEY (key);


--
-- Name: research_class_actor_assignments class_actor_assignment_unique; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.research_class_actor_assignments
    ADD CONSTRAINT class_actor_assignment_unique UNIQUE (research_class_id, actor_type, user_id);


--
-- Name: colleges colleges_code_unique; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.colleges
    ADD CONSTRAINT colleges_code_unique UNIQUE (code);


--
-- Name: colleges colleges_name_unique; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.colleges
    ADD CONSTRAINT colleges_name_unique UNIQUE (name);


--
-- Name: colleges colleges_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.colleges
    ADD CONSTRAINT colleges_pkey PRIMARY KEY (id);


--
-- Name: consultation_attendances consultation_attendances_consultation_record_id_student_id_uniq; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.consultation_attendances
    ADD CONSTRAINT consultation_attendances_consultation_record_id_student_id_uniq UNIQUE (consultation_record_id, student_id);


--
-- Name: consultation_attendances consultation_attendances_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.consultation_attendances
    ADD CONSTRAINT consultation_attendances_pkey PRIMARY KEY (id);


--
-- Name: consultation_audits consultation_audits_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.consultation_audits
    ADD CONSTRAINT consultation_audits_pkey PRIMARY KEY (id);


--
-- Name: consultation_records consultation_records_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.consultation_records
    ADD CONSTRAINT consultation_records_pkey PRIMARY KEY (id);


--
-- Name: consultation_requests consultation_requests_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.consultation_requests
    ADD CONSTRAINT consultation_requests_pkey PRIMARY KEY (id);


--
-- Name: consultation_requests consultation_requests_requested_by_request_token_unique; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.consultation_requests
    ADD CONSTRAINT consultation_requests_requested_by_request_token_unique UNIQUE (requested_by, request_token);


--
-- Name: consultation_schedule_proposals consultation_schedule_proposals_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.consultation_schedule_proposals
    ADD CONSTRAINT consultation_schedule_proposals_pkey PRIMARY KEY (id);


--
-- Name: defense_evaluations de_round_panelist_unique; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.defense_evaluations
    ADD CONSTRAINT de_round_panelist_unique UNIQUE (defense_evaluation_round_id, panelist_user_id);


--
-- Name: defense_evaluation_round_panelists defense_evaluation_round_panelists_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.defense_evaluation_round_panelists
    ADD CONSTRAINT defense_evaluation_round_panelists_pkey PRIMARY KEY (id);


--
-- Name: defense_evaluation_round_students defense_evaluation_round_students_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.defense_evaluation_round_students
    ADD CONSTRAINT defense_evaluation_round_students_pkey PRIMARY KEY (id);


--
-- Name: defense_evaluation_rounds defense_evaluation_rounds_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.defense_evaluation_rounds
    ADD CONSTRAINT defense_evaluation_rounds_pkey PRIMARY KEY (id);


--
-- Name: defense_evaluation_student_scores defense_evaluation_student_scores_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.defense_evaluation_student_scores
    ADD CONSTRAINT defense_evaluation_student_scores_pkey PRIMARY KEY (id);


--
-- Name: defense_evaluation_student_summaries defense_evaluation_student_summaries_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.defense_evaluation_student_summaries
    ADD CONSTRAINT defense_evaluation_student_summaries_pkey PRIMARY KEY (id);


--
-- Name: defense_evaluation_summaries defense_evaluation_summaries_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.defense_evaluation_summaries
    ADD CONSTRAINT defense_evaluation_summaries_pkey PRIMARY KEY (id);


--
-- Name: defense_evaluations defense_evaluations_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.defense_evaluations
    ADD CONSTRAINT defense_evaluations_pkey PRIMARY KEY (id);


--
-- Name: defense_panel_assignments defense_panel_assignments_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.defense_panel_assignments
    ADD CONSTRAINT defense_panel_assignments_pkey PRIMARY KEY (id);


--
-- Name: defense_rooms defense_rooms_code_unique; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.defense_rooms
    ADD CONSTRAINT defense_rooms_code_unique UNIQUE (code);


--
-- Name: defense_rooms defense_rooms_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.defense_rooms
    ADD CONSTRAINT defense_rooms_pkey PRIMARY KEY (id);


--
-- Name: defense_schedules defense_schedules_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.defense_schedules
    ADD CONSTRAINT defense_schedules_pkey PRIMARY KEY (id);


--
-- Name: defense_sessions defense_sessions_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.defense_sessions
    ADD CONSTRAINT defense_sessions_pkey PRIMARY KEY (id);


--
-- Name: defenses defenses_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.defenses
    ADD CONSTRAINT defenses_pkey PRIMARY KEY (id);


--
-- Name: departments departments_code_unique; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.departments
    ADD CONSTRAINT departments_code_unique UNIQUE (code);


--
-- Name: departments departments_college_id_name_unique; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.departments
    ADD CONSTRAINT departments_college_id_name_unique UNIQUE (college_id, name);


--
-- Name: departments departments_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.departments
    ADD CONSTRAINT departments_pkey PRIMARY KEY (id);


--
-- Name: defense_evaluation_round_panelists derp_round_panelist_unique; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.defense_evaluation_round_panelists
    ADD CONSTRAINT derp_round_panelist_unique UNIQUE (defense_evaluation_round_id, panelist_user_id);


--
-- Name: defense_evaluation_round_students ders_round_student_unique; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.defense_evaluation_round_students
    ADD CONSTRAINT ders_round_student_unique UNIQUE (defense_evaluation_round_id, student_id);


--
-- Name: defense_evaluation_summaries des_round_unique; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.defense_evaluation_summaries
    ADD CONSTRAINT des_round_unique UNIQUE (defense_evaluation_round_id);


--
-- Name: defense_evaluation_student_scores dess_eval_student_unique; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.defense_evaluation_student_scores
    ADD CONSTRAINT dess_eval_student_unique UNIQUE (defense_evaluation_id, student_id);


--
-- Name: defense_evaluation_student_summaries dessum_summary_student_unique; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.defense_evaluation_student_summaries
    ADD CONSTRAINT dessum_summary_student_unique UNIQUE (defense_evaluation_summary_id, student_id);


--
-- Name: document_access_audits document_access_audits_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.document_access_audits
    ADD CONSTRAINT document_access_audits_pkey PRIMARY KEY (id);


--
-- Name: document_review_audits document_review_audits_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.document_review_audits
    ADD CONSTRAINT document_review_audits_pkey PRIMARY KEY (id);


--
-- Name: document_review_comments document_review_comments_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.document_review_comments
    ADD CONSTRAINT document_review_comments_pkey PRIMARY KEY (id);


--
-- Name: document_reviews document_reviews_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.document_reviews
    ADD CONSTRAINT document_reviews_pkey PRIMARY KEY (id);


--
-- Name: document_upload_audits document_upload_audits_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.document_upload_audits
    ADD CONSTRAINT document_upload_audits_pkey PRIMARY KEY (id);


--
-- Name: documents documents_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.documents
    ADD CONSTRAINT documents_pkey PRIMARY KEY (id);


--
-- Name: documents documents_storage_path_unique; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.documents
    ADD CONSTRAINT documents_storage_path_unique UNIQUE (storage_path);


--
-- Name: documents documents_user_id_submission_token_unique; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.documents
    ADD CONSTRAINT documents_user_id_submission_token_unique UNIQUE (user_id, submission_token);


--
-- Name: faculty_profile_departments faculty_profile_department_unique; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.faculty_profile_departments
    ADD CONSTRAINT faculty_profile_department_unique UNIQUE (faculty_profile_id, department_id);


--
-- Name: faculty_profile_departments faculty_profile_departments_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.faculty_profile_departments
    ADD CONSTRAINT faculty_profile_departments_pkey PRIMARY KEY (id);


--
-- Name: faculty_profiles faculty_profiles_employee_number_unique; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.faculty_profiles
    ADD CONSTRAINT faculty_profiles_employee_number_unique UNIQUE (employee_number);


--
-- Name: faculty_profiles faculty_profiles_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.faculty_profiles
    ADD CONSTRAINT faculty_profiles_pkey PRIMARY KEY (id);


--
-- Name: faculty_profiles faculty_profiles_user_id_unique; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.faculty_profiles
    ADD CONSTRAINT faculty_profiles_user_id_unique UNIQUE (user_id);


--
-- Name: failed_jobs failed_jobs_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.failed_jobs
    ADD CONSTRAINT failed_jobs_pkey PRIMARY KEY (id);


--
-- Name: failed_jobs failed_jobs_uuid_unique; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.failed_jobs
    ADD CONSTRAINT failed_jobs_uuid_unique UNIQUE (uuid);


--
-- Name: official_form_actor_assignments form_actor_assignment_unique; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.official_form_actor_assignments
    ADD CONSTRAINT form_actor_assignment_unique UNIQUE (official_form_instance_id, actor_type, user_id);


--
-- Name: research_class_group_member_histories group_member_history_unique; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.research_class_group_member_histories
    ADD CONSTRAINT group_member_history_unique UNIQUE (research_class_group_id, student_id);


--
-- Name: research_group_milestones group_milestone_unique; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.research_group_milestones
    ADD CONSTRAINT group_milestone_unique UNIQUE (research_class_group_id, milestone_definition_id);


--
-- Name: official_form_signatures idx_official_form_signatures_unique; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.official_form_signatures
    ADD CONSTRAINT idx_official_form_signatures_unique UNIQUE (official_form_version_id, signer_user_id, actor_type, academic_action);


--
-- Name: job_batches job_batches_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.job_batches
    ADD CONSTRAINT job_batches_pkey PRIMARY KEY (id);


--
-- Name: jobs jobs_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.jobs
    ADD CONSTRAINT jobs_pkey PRIMARY KEY (id);


--
-- Name: migrations migrations_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.migrations
    ADD CONSTRAINT migrations_pkey PRIMARY KEY (id);


--
-- Name: milestone_definitions milestone_definitions_code_unique; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.milestone_definitions
    ADD CONSTRAINT milestone_definitions_code_unique UNIQUE (code);


--
-- Name: milestone_definitions milestone_definitions_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.milestone_definitions
    ADD CONSTRAINT milestone_definitions_pkey PRIMARY KEY (id);


--
-- Name: milestone_definitions milestone_definitions_sequence_unique; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.milestone_definitions
    ADD CONSTRAINT milestone_definitions_sequence_unique UNIQUE (sequence);


--
-- Name: milestone_evidences milestone_evidence_unique; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.milestone_evidences
    ADD CONSTRAINT milestone_evidence_unique UNIQUE (research_group_milestone_id, evidence_type, evidence_id);


--
-- Name: milestone_evidences milestone_evidences_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.milestone_evidences
    ADD CONSTRAINT milestone_evidences_pkey PRIMARY KEY (id);


--
-- Name: model_has_permissions model_has_permissions_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.model_has_permissions
    ADD CONSTRAINT model_has_permissions_pkey PRIMARY KEY (permission_id, model_id, model_type);


--
-- Name: model_has_roles model_has_roles_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.model_has_roles
    ADD CONSTRAINT model_has_roles_pkey PRIMARY KEY (role_id, model_id, model_type);


--
-- Name: notifications notifications_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.notifications
    ADD CONSTRAINT notifications_pkey PRIMARY KEY (id);


--
-- Name: official_form_actor_assignments official_form_actor_assignments_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.official_form_actor_assignments
    ADD CONSTRAINT official_form_actor_assignments_pkey PRIMARY KEY (id);


--
-- Name: official_form_definitions official_form_definitions_code_unique; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.official_form_definitions
    ADD CONSTRAINT official_form_definitions_code_unique UNIQUE (code);


--
-- Name: official_form_definitions official_form_definitions_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.official_form_definitions
    ADD CONSTRAINT official_form_definitions_pkey PRIMARY KEY (id);


--
-- Name: official_form_instances official_form_instances_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.official_form_instances
    ADD CONSTRAINT official_form_instances_pkey PRIMARY KEY (id);


--
-- Name: official_form_signature_verifications official_form_signature_verifications_official_for_15f044f0b3b1; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.official_form_signature_verifications
    ADD CONSTRAINT official_form_signature_verifications_official_for_15f044f0b3b1 UNIQUE (official_form_signature_id);


--
-- Name: official_form_signature_verifications official_form_signature_verifications_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.official_form_signature_verifications
    ADD CONSTRAINT official_form_signature_verifications_pkey PRIMARY KEY (id);


--
-- Name: official_form_signature_verifications official_form_signature_verifications_public_reference_unique; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.official_form_signature_verifications
    ADD CONSTRAINT official_form_signature_verifications_public_reference_unique UNIQUE (public_reference);


--
-- Name: official_form_signatures official_form_signatures_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.official_form_signatures
    ADD CONSTRAINT official_form_signatures_pkey PRIMARY KEY (id);


--
-- Name: official_form_verifications official_form_verifications_official_form_version_id_unique; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.official_form_verifications
    ADD CONSTRAINT official_form_verifications_official_form_version_id_unique UNIQUE (official_form_version_id);


--
-- Name: official_form_verifications official_form_verifications_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.official_form_verifications
    ADD CONSTRAINT official_form_verifications_pkey PRIMARY KEY (id);


--
-- Name: official_form_verifications official_form_verifications_public_reference_unique; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.official_form_verifications
    ADD CONSTRAINT official_form_verifications_public_reference_unique UNIQUE (public_reference);


--
-- Name: official_form_versions official_form_versions_official_form_instance_id_version_number; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.official_form_versions
    ADD CONSTRAINT official_form_versions_official_form_instance_id_version_number UNIQUE (official_form_instance_id, version_number);


--
-- Name: official_form_versions official_form_versions_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.official_form_versions
    ADD CONSTRAINT official_form_versions_pkey PRIMARY KEY (id);


--
-- Name: password_reset_tokens password_reset_tokens_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.password_reset_tokens
    ADD CONSTRAINT password_reset_tokens_pkey PRIMARY KEY (email);


--
-- Name: permissions permissions_name_guard_name_unique; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.permissions
    ADD CONSTRAINT permissions_name_guard_name_unique UNIQUE (name, guard_name);


--
-- Name: permissions permissions_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.permissions
    ADD CONSTRAINT permissions_pkey PRIMARY KEY (id);


--
-- Name: programs programs_code_unique; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.programs
    ADD CONSTRAINT programs_code_unique UNIQUE (code);


--
-- Name: programs programs_department_id_name_unique; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.programs
    ADD CONSTRAINT programs_department_id_name_unique UNIQUE (department_id, name);


--
-- Name: programs programs_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.programs
    ADD CONSTRAINT programs_pkey PRIMARY KEY (id);


--
-- Name: research_class_panel_committees rc_panel_comm_class_type_unique; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.research_class_panel_committees
    ADD CONSTRAINT rc_panel_comm_class_type_unique UNIQUE (research_class_id, defense_type);


--
-- Name: research_class_panel_members rc_panel_members_position_unique; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.research_class_panel_members
    ADD CONSTRAINT rc_panel_members_position_unique UNIQUE (committee_id, panel_position);


--
-- Name: research_class_panel_members rc_panel_members_user_unique; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.research_class_panel_members
    ADD CONSTRAINT rc_panel_members_user_unique UNIQUE (committee_id, user_id);


--
-- Name: research_class_actor_assignments research_class_actor_assignments_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.research_class_actor_assignments
    ADD CONSTRAINT research_class_actor_assignments_pkey PRIMARY KEY (id);


--
-- Name: research_class_enrollments research_class_enrollments_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.research_class_enrollments
    ADD CONSTRAINT research_class_enrollments_pkey PRIMARY KEY (id);


--
-- Name: research_class_group_adviser_histories research_class_group_adviser_histories_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.research_class_group_adviser_histories
    ADD CONSTRAINT research_class_group_adviser_histories_pkey PRIMARY KEY (id);


--
-- Name: research_class_group_adviser_requests research_class_group_adviser_requests_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.research_class_group_adviser_requests
    ADD CONSTRAINT research_class_group_adviser_requests_pkey PRIMARY KEY (id);


--
-- Name: research_class_group_member_histories research_class_group_member_histories_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.research_class_group_member_histories
    ADD CONSTRAINT research_class_group_member_histories_pkey PRIMARY KEY (id);


--
-- Name: research_class_group_members research_class_group_members_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.research_class_group_members
    ADD CONSTRAINT research_class_group_members_pkey PRIMARY KEY (id);


--
-- Name: research_class_group_members research_class_group_members_research_class_enrollment_id_uniqu; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.research_class_group_members
    ADD CONSTRAINT research_class_group_members_research_class_enrollment_id_uniqu UNIQUE (research_class_enrollment_id);


--
-- Name: research_class_group_members research_class_group_members_research_class_group_id_student_id; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.research_class_group_members
    ADD CONSTRAINT research_class_group_members_research_class_group_id_student_id UNIQUE (research_class_group_id, student_id);


--
-- Name: research_class_group_members research_class_group_members_research_class_id_student_id_uniqu; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.research_class_group_members
    ADD CONSTRAINT research_class_group_members_research_class_id_student_id_uniqu UNIQUE (research_class_id, student_id);


--
-- Name: research_class_groups research_class_groups_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.research_class_groups
    ADD CONSTRAINT research_class_groups_pkey PRIMARY KEY (id);


--
-- Name: research_class_groups research_class_groups_research_class_id_creation_token_unique; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.research_class_groups
    ADD CONSTRAINT research_class_groups_research_class_id_creation_token_unique UNIQUE (research_class_id, creation_token);


--
-- Name: research_class_groups research_class_groups_research_class_id_name_unique; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.research_class_groups
    ADD CONSTRAINT research_class_groups_research_class_id_name_unique UNIQUE (research_class_id, name);


--
-- Name: research_class_groups research_class_groups_research_group_id_unique; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.research_class_groups
    ADD CONSTRAINT research_class_groups_research_group_id_unique UNIQUE (research_group_id);


--
-- Name: research_class_panel_committees research_class_panel_committees_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.research_class_panel_committees
    ADD CONSTRAINT research_class_panel_committees_pkey PRIMARY KEY (id);


--
-- Name: research_class_panel_members research_class_panel_members_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.research_class_panel_members
    ADD CONSTRAINT research_class_panel_members_pkey PRIMARY KEY (id);


--
-- Name: research_classes research_classes_adviser_id_creation_token_unique; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.research_classes
    ADD CONSTRAINT research_classes_adviser_id_creation_token_unique UNIQUE (facilitator_id, creation_token);


--
-- Name: research_classes research_classes_join_code_hash_unique; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.research_classes
    ADD CONSTRAINT research_classes_join_code_hash_unique UNIQUE (join_code_hash);


--
-- Name: research_classes research_classes_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.research_classes
    ADD CONSTRAINT research_classes_pkey PRIMARY KEY (id);


--
-- Name: research_group_adviser_change_requests research_group_adviser_change_requests_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.research_group_adviser_change_requests
    ADD CONSTRAINT research_group_adviser_change_requests_pkey PRIMARY KEY (id);


--
-- Name: research_group_members research_group_members_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.research_group_members
    ADD CONSTRAINT research_group_members_pkey PRIMARY KEY (id);


--
-- Name: research_group_members research_group_members_research_group_id_student_profile_id_uni; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.research_group_members
    ADD CONSTRAINT research_group_members_research_group_id_student_profile_id_uni UNIQUE (research_group_id, student_profile_id);


--
-- Name: research_group_milestone_events research_group_milestone_events_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.research_group_milestone_events
    ADD CONSTRAINT research_group_milestone_events_pkey PRIMARY KEY (id);


--
-- Name: research_group_milestones research_group_milestones_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.research_group_milestones
    ADD CONSTRAINT research_group_milestones_pkey PRIMARY KEY (id);


--
-- Name: research_group_panel_committees research_group_panel_committees_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.research_group_panel_committees
    ADD CONSTRAINT research_group_panel_committees_pkey PRIMARY KEY (id);


--
-- Name: research_group_panel_members research_group_panel_members_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.research_group_panel_members
    ADD CONSTRAINT research_group_panel_members_pkey PRIMARY KEY (id);


--
-- Name: research_groups research_groups_academic_term_id_name_unique; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.research_groups
    ADD CONSTRAINT research_groups_academic_term_id_name_unique UNIQUE (academic_term_id, name);


--
-- Name: research_groups research_groups_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.research_groups
    ADD CONSTRAINT research_groups_pkey PRIMARY KEY (id);


--
-- Name: research_project_title_histories research_project_title_histories_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.research_project_title_histories
    ADD CONSTRAINT research_project_title_histories_pkey PRIMARY KEY (id);


--
-- Name: research_projects research_projects_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.research_projects
    ADD CONSTRAINT research_projects_pkey PRIMARY KEY (id);


--
-- Name: research_proposals research_proposals_document_id_unique; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.research_proposals
    ADD CONSTRAINT research_proposals_document_id_unique UNIQUE (document_id);


--
-- Name: research_proposals research_proposals_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.research_proposals
    ADD CONSTRAINT research_proposals_pkey PRIMARY KEY (id);


--
-- Name: revision_request_events revision_request_events_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.revision_request_events
    ADD CONSTRAINT revision_request_events_pkey PRIMARY KEY (id);


--
-- Name: revision_requests revision_requests_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.revision_requests
    ADD CONSTRAINT revision_requests_pkey PRIMARY KEY (id);


--
-- Name: revision_requests revision_requests_source_review_unique; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.revision_requests
    ADD CONSTRAINT revision_requests_source_review_unique UNIQUE (source_document_review_id);


--
-- Name: research_group_panel_committees rg_panel_comm_group_type_unique; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.research_group_panel_committees
    ADD CONSTRAINT rg_panel_comm_group_type_unique UNIQUE (research_class_group_id, defense_type);


--
-- Name: research_group_panel_members rg_panel_members_position_unique; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.research_group_panel_members
    ADD CONSTRAINT rg_panel_members_position_unique UNIQUE (committee_id, panel_position);


--
-- Name: research_group_panel_members rg_panel_members_user_unique; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.research_group_panel_members
    ADD CONSTRAINT rg_panel_members_user_unique UNIQUE (committee_id, user_id);


--
-- Name: role_has_permissions role_has_permissions_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.role_has_permissions
    ADD CONSTRAINT role_has_permissions_pkey PRIMARY KEY (permission_id, role_id);


--
-- Name: roles roles_name_guard_name_unique; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.roles
    ADD CONSTRAINT roles_name_guard_name_unique UNIQUE (name, guard_name);


--
-- Name: roles roles_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.roles
    ADD CONSTRAINT roles_pkey PRIMARY KEY (id);


--
-- Name: sessions sessions_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.sessions
    ADD CONSTRAINT sessions_pkey PRIMARY KEY (id);


--
-- Name: signature_audits signature_audits_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.signature_audits
    ADD CONSTRAINT signature_audits_pkey PRIMARY KEY (id);


--
-- Name: student_profiles student_profiles_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.student_profiles
    ADD CONSTRAINT student_profiles_pkey PRIMARY KEY (id);


--
-- Name: student_profiles student_profiles_student_number_unique; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.student_profiles
    ADD CONSTRAINT student_profiles_student_number_unique UNIQUE (student_number);


--
-- Name: student_profiles student_profiles_user_id_unique; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.student_profiles
    ADD CONSTRAINT student_profiles_user_id_unique UNIQUE (user_id);


--
-- Name: system_backup_settings system_backup_settings_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.system_backup_settings
    ADD CONSTRAINT system_backup_settings_pkey PRIMARY KEY (id);


--
-- Name: system_backups system_backups_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.system_backups
    ADD CONSTRAINT system_backups_pkey PRIMARY KEY (id);


--
-- Name: system_settings system_settings_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.system_settings
    ADD CONSTRAINT system_settings_pkey PRIMARY KEY (id);


--
-- Name: title_presentations title_presentations_defense_id_unique; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.title_presentations
    ADD CONSTRAINT title_presentations_defense_id_unique UNIQUE (defense_id);


--
-- Name: title_presentations title_presentations_official_form_instance_id_unique; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.title_presentations
    ADD CONSTRAINT title_presentations_official_form_instance_id_unique UNIQUE (official_form_instance_id);


--
-- Name: title_presentations title_presentations_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.title_presentations
    ADD CONSTRAINT title_presentations_pkey PRIMARY KEY (id);


--
-- Name: user_onboarding_completions user_onboarding_completions_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.user_onboarding_completions
    ADD CONSTRAINT user_onboarding_completions_pkey PRIMARY KEY (id);


--
-- Name: user_onboarding_completions user_onboarding_workspace_unique; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.user_onboarding_completions
    ADD CONSTRAINT user_onboarding_workspace_unique UNIQUE (user_id, workspace);


--
-- Name: user_signatures user_signatures_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.user_signatures
    ADD CONSTRAINT user_signatures_pkey PRIMARY KEY (id);


--
-- Name: user_signatures user_signatures_storage_path_unique; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.user_signatures
    ADD CONSTRAINT user_signatures_storage_path_unique UNIQUE (storage_path);


--
-- Name: user_signatures user_signatures_user_id_unique; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.user_signatures
    ADD CONSTRAINT user_signatures_user_id_unique UNIQUE (user_id);


--
-- Name: users users_email_unique; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.users
    ADD CONSTRAINT users_email_unique UNIQUE (email);


--
-- Name: users users_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.users
    ADD CONSTRAINT users_pkey PRIMARY KEY (id);


--
-- Name: users users_student_id_unique; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.users
    ADD CONSTRAINT users_student_id_unique UNIQUE (student_id);


--
-- Name: adviser_assignments_adviser_id_status_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX adviser_assignments_adviser_id_status_index ON public.adviser_assignments USING btree (adviser_id, status);


--
-- Name: adviser_assignments_research_project_id_status_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX adviser_assignments_research_project_id_status_index ON public.adviser_assignments USING btree (research_project_id, status);


--
-- Name: adviser_change_group_status_idx; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX adviser_change_group_status_idx ON public.research_group_adviser_change_requests USING btree (research_class_group_id, status);


--
-- Name: adviser_change_one_pending_per_group; Type: INDEX; Schema: public; Owner: -
--

CREATE UNIQUE INDEX adviser_change_one_pending_per_group ON public.research_group_adviser_change_requests USING btree (research_class_group_id) WHERE ((status)::text = 'submitted'::text);


--
-- Name: adviser_change_requested_status_idx; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX adviser_change_requested_status_idx ON public.research_group_adviser_change_requests USING btree (requested_adviser_id, status);


--
-- Name: audit_logs_actor_context_created_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX audit_logs_actor_context_created_index ON public.audit_logs USING btree (actor_context, created_at);


--
-- Name: audit_logs_auditable_type_auditable_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX audit_logs_auditable_type_auditable_id_index ON public.audit_logs USING btree (auditable_type, auditable_id);


--
-- Name: audit_logs_event_created_at_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX audit_logs_event_created_at_index ON public.audit_logs USING btree (event, created_at);


--
-- Name: audit_logs_outcome_created_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX audit_logs_outcome_created_index ON public.audit_logs USING btree (outcome, created_at);


--
-- Name: audit_logs_subject_email_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX audit_logs_subject_email_index ON public.audit_logs USING btree (subject_email);


--
-- Name: audit_logs_user_id_created_at_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX audit_logs_user_id_created_at_index ON public.audit_logs USING btree (user_id, created_at);


--
-- Name: cache_expiration_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX cache_expiration_index ON public.cache USING btree (expiration);


--
-- Name: cache_locks_expiration_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX cache_locks_expiration_index ON public.cache_locks USING btree (expiration);


--
-- Name: class_actor_user_lookup; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX class_actor_user_lookup ON public.research_class_actor_assignments USING btree (user_id, actor_type, status);


--
-- Name: class_enrollments_request_queue_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX class_enrollments_request_queue_index ON public.research_class_enrollments USING btree (research_class_id, status, requested_at);


--
-- Name: class_enrollments_student_class_status_reviewed_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX class_enrollments_student_class_status_reviewed_index ON public.research_class_enrollments USING btree (student_id, research_class_id, status, reviewed_at);


--
-- Name: class_enrollments_student_status_requested_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX class_enrollments_student_status_requested_index ON public.research_class_enrollments USING btree (student_id, status, requested_at);


--
-- Name: consultation_audits_consultation_request_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX consultation_audits_consultation_request_id_index ON public.consultation_audits USING btree (consultation_request_id);


--
-- Name: consultation_audits_research_class_group_id_action_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX consultation_audits_research_class_group_id_action_index ON public.consultation_audits USING btree (research_class_group_id, action);


--
-- Name: consultation_records_conducted_by_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX consultation_records_conducted_by_index ON public.consultation_records USING btree (conducted_by);


--
-- Name: consultation_records_consultation_request_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX consultation_records_consultation_request_id_index ON public.consultation_records USING btree (consultation_request_id);


--
-- Name: consultation_records_research_class_group_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX consultation_records_research_class_group_id_index ON public.consultation_records USING btree (research_class_group_id);


--
-- Name: consultation_requests_adviser_assignment_id_status_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX consultation_requests_adviser_assignment_id_status_index ON public.consultation_requests USING btree (adviser_assignment_id, status);


--
-- Name: consultation_requests_research_project_id_status_preferred_at_i; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX consultation_requests_research_project_id_status_preferred_at_i ON public.consultation_requests USING btree (research_project_id, status, preferred_at);


--
-- Name: consultation_schedule_proposals_consultation_request_id_status_; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX consultation_schedule_proposals_consultation_request_id_status_ ON public.consultation_schedule_proposals USING btree (consultation_request_id, status);


--
-- Name: defense_evaluation_rounds_defense_id_status_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX defense_evaluation_rounds_defense_id_status_index ON public.defense_evaluation_rounds USING btree (defense_id, status);


--
-- Name: defense_evaluation_rounds_defense_schedule_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX defense_evaluation_rounds_defense_schedule_id_index ON public.defense_evaluation_rounds USING btree (defense_schedule_id);


--
-- Name: defense_panel_assignments_defense_id_user_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX defense_panel_assignments_defense_id_user_id_index ON public.defense_panel_assignments USING btree (defense_id, user_id);


--
-- Name: defense_panel_assignments_user_id_ended_at_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX defense_panel_assignments_user_id_ended_at_index ON public.defense_panel_assignments USING btree (user_id, ended_at);


--
-- Name: defense_panel_position_active_idx; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX defense_panel_position_active_idx ON public.defense_panel_assignments USING btree (defense_id, panel_position, ended_at);


--
-- Name: defense_rooms_is_active_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX defense_rooms_is_active_index ON public.defense_rooms USING btree (is_active);


--
-- Name: defense_schedules_defense_id_status_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX defense_schedules_defense_id_status_index ON public.defense_schedules USING btree (defense_id, status);


--
-- Name: defense_schedules_defense_session_id_presentation_order_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX defense_schedules_defense_session_id_presentation_order_index ON public.defense_schedules USING btree (defense_session_id, presentation_order);


--
-- Name: defense_schedules_room_id_status_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX defense_schedules_room_id_status_index ON public.defense_schedules USING btree (room_id, status);


--
-- Name: defense_schedules_starts_at_ends_at_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX defense_schedules_starts_at_ends_at_index ON public.defense_schedules USING btree (starts_at, ends_at);


--
-- Name: defense_sessions_session_date_room_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX defense_sessions_session_date_room_id_index ON public.defense_sessions USING btree (session_date, room_id);


--
-- Name: defense_sessions_starts_at_ends_at_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX defense_sessions_starts_at_ends_at_index ON public.defense_sessions USING btree (starts_at, ends_at);


--
-- Name: defenses_research_class_group_id_defense_type_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX defenses_research_class_group_id_defense_type_index ON public.defenses USING btree (research_class_group_id, defense_type);


--
-- Name: department_faculty_profile_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX department_faculty_profile_index ON public.faculty_profile_departments USING btree (department_id, faculty_profile_id);


--
-- Name: document_access_audits_action_accessed_at_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX document_access_audits_action_accessed_at_index ON public.document_access_audits USING btree (action, accessed_at);


--
-- Name: document_access_audits_document_id_accessed_at_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX document_access_audits_document_id_accessed_at_index ON public.document_access_audits USING btree (document_id, accessed_at);


--
-- Name: document_access_audits_user_id_accessed_at_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX document_access_audits_user_id_accessed_at_index ON public.document_access_audits USING btree (user_id, accessed_at);


--
-- Name: document_review_audits_action_decision_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX document_review_audits_action_decision_index ON public.document_review_audits USING btree (action, decision);


--
-- Name: document_review_audits_document_id_occurred_at_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX document_review_audits_document_id_occurred_at_index ON public.document_review_audits USING btree (document_id, occurred_at);


--
-- Name: document_review_audits_reviewer_id_occurred_at_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX document_review_audits_reviewer_id_occurred_at_index ON public.document_review_audits USING btree (reviewer_id, occurred_at);


--
-- Name: document_review_audits_student_id_occurred_at_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX document_review_audits_student_id_occurred_at_index ON public.document_review_audits USING btree (student_id, occurred_at);


--
-- Name: document_review_comments_author_id_created_at_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX document_review_comments_author_id_created_at_index ON public.document_review_comments USING btree (author_id, created_at);


--
-- Name: document_review_comments_document_id_resolved_at_created_at_ind; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX document_review_comments_document_id_resolved_at_created_at_ind ON public.document_review_comments USING btree (document_id, resolved_at, created_at);


--
-- Name: document_review_comments_severity_resolved_at_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX document_review_comments_severity_resolved_at_index ON public.document_review_comments USING btree (severity, resolved_at);


--
-- Name: document_reviews_decision_reviewed_at_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX document_reviews_decision_reviewed_at_index ON public.document_reviews USING btree (decision, reviewed_at);


--
-- Name: document_reviews_document_id_is_superseded_reviewed_at_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX document_reviews_document_id_is_superseded_reviewed_at_index ON public.document_reviews USING btree (document_id, is_superseded, reviewed_at);


--
-- Name: document_reviews_document_id_reviewed_at_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX document_reviews_document_id_reviewed_at_index ON public.document_reviews USING btree (document_id, reviewed_at);


--
-- Name: document_reviews_reviewer_id_reviewed_at_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX document_reviews_reviewer_id_reviewed_at_index ON public.document_reviews USING btree (reviewer_id, reviewed_at);


--
-- Name: document_upload_audits_research_class_group_id_attempted_at_ind; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX document_upload_audits_research_class_group_id_attempted_at_ind ON public.document_upload_audits USING btree (research_class_group_id, attempted_at);


--
-- Name: document_upload_audits_upload_status_attempted_at_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX document_upload_audits_upload_status_attempted_at_index ON public.document_upload_audits USING btree (upload_status, attempted_at);


--
-- Name: document_upload_audits_user_id_attempted_at_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX document_upload_audits_user_id_attempted_at_index ON public.document_upload_audits USING btree (user_id, attempted_at);


--
-- Name: documents_content_sha256_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX documents_content_sha256_index ON public.documents USING btree (content_sha256);


--
-- Name: documents_repository_stream_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX documents_repository_stream_index ON public.documents USING btree (research_class_group_id, document_stage, is_current, submitted_at);


--
-- Name: documents_research_class_group_id_is_current_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX documents_research_class_group_id_is_current_index ON public.documents USING btree (research_class_group_id, is_current);


--
-- Name: documents_revision_request_id_submitted_at_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX documents_revision_request_id_submitted_at_index ON public.documents USING btree (revision_request_id, submitted_at);


--
-- Name: documents_status_submitted_at_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX documents_status_submitted_at_index ON public.documents USING btree (status, submitted_at);


--
-- Name: documents_user_id_submitted_at_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX documents_user_id_submitted_at_index ON public.documents USING btree (user_id, submitted_at);


--
-- Name: failed_jobs_connection_queue_failed_at_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX failed_jobs_connection_queue_failed_at_index ON public.failed_jobs USING btree (connection, queue, failed_at);


--
-- Name: idx_form_instance_class_lookup; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX idx_form_instance_class_lookup ON public.official_form_instances USING btree (research_class_id, official_form_definition_id, status);


--
-- Name: idx_form_instance_group_lookup; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX idx_form_instance_group_lookup ON public.official_form_instances USING btree (research_class_group_id, official_form_definition_id, status);


--
-- Name: idx_form_instance_source; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX idx_form_instance_source ON public.official_form_instances USING btree (source_type, source_id);


--
-- Name: idx_form_signatures_instance_version; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX idx_form_signatures_instance_version ON public.official_form_signatures USING btree (official_form_instance_id, official_form_version_id);


--
-- Name: jobs_queue_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX jobs_queue_index ON public.jobs USING btree (queue);


--
-- Name: milestone_definitions_is_active_sequence_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX milestone_definitions_is_active_sequence_index ON public.milestone_definitions USING btree (is_active, sequence);


--
-- Name: milestone_event_history_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX milestone_event_history_index ON public.research_group_milestone_events USING btree (research_group_milestone_id, occurred_at);


--
-- Name: milestone_evidences_evidence_type_evidence_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX milestone_evidences_evidence_type_evidence_id_index ON public.milestone_evidences USING btree (evidence_type, evidence_id);


--
-- Name: model_has_permissions_model_id_model_type_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX model_has_permissions_model_id_model_type_index ON public.model_has_permissions USING btree (model_id, model_type);


--
-- Name: model_has_roles_model_id_model_type_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX model_has_roles_model_id_model_type_index ON public.model_has_roles USING btree (model_id, model_type);


--
-- Name: notifications_notifiable_type_notifiable_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX notifications_notifiable_type_notifiable_id_index ON public.notifications USING btree (notifiable_type, notifiable_id);


--
-- Name: notifications_recipient_read_created_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX notifications_recipient_read_created_index ON public.notifications USING btree (notifiable_type, notifiable_id, read_at, created_at);


--
-- Name: official_form_actor_assignments_user_id_actor_type_status_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX official_form_actor_assignments_user_id_actor_type_status_index ON public.official_form_actor_assignments USING btree (user_id, actor_type, status);


--
-- Name: official_form_instances_defense_evaluation_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX official_form_instances_defense_evaluation_id_index ON public.official_form_instances USING btree (defense_evaluation_id);


--
-- Name: official_form_versions_official_form_instance_id_is_current_ind; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX official_form_versions_official_form_instance_id_is_current_ind ON public.official_form_versions USING btree (official_form_instance_id, is_current);


--
-- Name: onboarding_workspace_status_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX onboarding_workspace_status_index ON public.user_onboarding_completions USING btree (workspace, status);


--
-- Name: permissions_module_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX permissions_module_index ON public.permissions USING btree (module);


--
-- Name: research_class_enrollments_student_id_status_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX research_class_enrollments_student_id_status_index ON public.research_class_enrollments USING btree (student_id, status);


--
-- Name: research_class_group_adviser_histories_research_class_group_id_; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX research_class_group_adviser_histories_research_class_group_id_ ON public.research_class_group_adviser_histories USING btree (research_class_group_id, adviser_id);


--
-- Name: research_class_group_adviser_requests_adviser_id_status_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX research_class_group_adviser_requests_adviser_id_status_index ON public.research_class_group_adviser_requests USING btree (adviser_id, status);


--
-- Name: research_class_group_adviser_requests_research_class_group_id_s; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX research_class_group_adviser_requests_research_class_group_id_s ON public.research_class_group_adviser_requests USING btree (research_class_group_id, status);


--
-- Name: research_class_group_member_histories_student_id_archived_at_in; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX research_class_group_member_histories_student_id_archived_at_in ON public.research_class_group_member_histories USING btree (student_id, archived_at);


--
-- Name: research_class_group_members_research_class_group_id_created_at; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX research_class_group_members_research_class_group_id_created_at ON public.research_class_group_members USING btree (research_class_group_id, created_at);


--
-- Name: research_class_groups_adviser_id_research_class_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX research_class_groups_adviser_id_research_class_id_index ON public.research_class_groups USING btree (adviser_id, research_class_id);


--
-- Name: research_class_groups_research_class_id_status_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX research_class_groups_research_class_id_status_index ON public.research_class_groups USING btree (research_class_id, status);


--
-- Name: research_classes_adviser_id_is_active_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX research_classes_adviser_id_is_active_index ON public.research_classes USING btree (facilitator_id, is_active);


--
-- Name: research_group_milestone_events_actor_id_occurred_at_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX research_group_milestone_events_actor_id_occurred_at_index ON public.research_group_milestone_events USING btree (actor_id, occurred_at);


--
-- Name: research_group_milestones_research_class_group_id_status_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX research_group_milestones_research_class_group_id_status_index ON public.research_group_milestones USING btree (research_class_group_id, status);


--
-- Name: research_group_milestones_status_due_at_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX research_group_milestones_status_due_at_index ON public.research_group_milestones USING btree (status, due_at);


--
-- Name: research_project_title_histories_changed_by_effective_at_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX research_project_title_histories_changed_by_effective_at_index ON public.research_project_title_histories USING btree (changed_by, effective_at);


--
-- Name: research_project_title_histories_research_project__78817b8b4d6c; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX research_project_title_histories_research_project__78817b8b4d6c ON public.research_project_title_histories USING btree (research_project_id, effective_at);


--
-- Name: research_projects_research_group_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX research_projects_research_group_id_index ON public.research_projects USING btree (research_group_id);


--
-- Name: research_projects_status_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX research_projects_status_index ON public.research_projects USING btree (status);


--
-- Name: research_proposals_research_project_id_version_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX research_proposals_research_project_id_version_index ON public.research_proposals USING btree (research_project_id, version);


--
-- Name: research_proposals_status_reviewed_at_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX research_proposals_status_reviewed_at_index ON public.research_proposals USING btree (status, reviewed_at);


--
-- Name: research_proposals_submitted_by_status_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX research_proposals_submitted_by_status_index ON public.research_proposals USING btree (submitted_by, status);


--
-- Name: rev_req_due_at_idx; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX rev_req_due_at_idx ON public.revision_requests USING btree (due_at);


--
-- Name: rev_req_group_status_idx; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX rev_req_group_status_idx ON public.revision_requests USING btree (research_class_group_id, status);


--
-- Name: rev_req_submitted_doc_idx; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX rev_req_submitted_doc_idx ON public.revision_requests USING btree (submitted_document_id);


--
-- Name: revision_request_events_action_to_status_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX revision_request_events_action_to_status_index ON public.revision_request_events USING btree (action, to_status);


--
-- Name: revision_request_events_actor_id_occurred_at_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX revision_request_events_actor_id_occurred_at_index ON public.revision_request_events USING btree (actor_id, occurred_at);


--
-- Name: revision_request_events_revision_request_id_occurred_at_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX revision_request_events_revision_request_id_occurred_at_index ON public.revision_request_events USING btree (revision_request_id, occurred_at);


--
-- Name: revision_requests_assigned_to_status_created_at_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX revision_requests_assigned_to_status_created_at_index ON public.revision_requests USING btree (assigned_to, status, created_at);


--
-- Name: revision_requests_document_id_status_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX revision_requests_document_id_status_index ON public.revision_requests USING btree (document_id, status);


--
-- Name: revision_requests_research_project_id_status_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX revision_requests_research_project_id_status_index ON public.revision_requests USING btree (research_project_id, status);


--
-- Name: roles_is_assignable_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX roles_is_assignable_index ON public.roles USING btree (is_assignable);


--
-- Name: roles_is_system_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX roles_is_system_index ON public.roles USING btree (is_system);


--
-- Name: sessions_last_activity_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX sessions_last_activity_index ON public.sessions USING btree (last_activity);


--
-- Name: sessions_user_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX sessions_user_id_index ON public.sessions USING btree (user_id);


--
-- Name: signature_audits_action_occurred_at_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX signature_audits_action_occurred_at_index ON public.signature_audits USING btree (action, occurred_at);


--
-- Name: signature_audits_user_id_occurred_at_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX signature_audits_user_id_occurred_at_index ON public.signature_audits USING btree (user_id, occurred_at);


--
-- Name: system_backups_restore_status_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX system_backups_restore_status_index ON public.system_backups USING btree (restore_status);


--
-- Name: system_backups_status_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX system_backups_status_index ON public.system_backups USING btree (status);


--
-- Name: system_backups_trigger_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX system_backups_trigger_index ON public.system_backups USING btree (trigger);


--
-- Name: system_backups_verification_status_verified_at_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX system_backups_verification_status_verified_at_index ON public.system_backups USING btree (verification_status, verified_at);


--
-- Name: title_presentation_version_status_idx; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX title_presentation_version_status_idx ON public.title_presentations USING btree (official_form_version_id, status);


--
-- Name: title_presentations_status_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX title_presentations_status_index ON public.title_presentations USING btree (status);


--
-- Name: user_signatures_content_sha256_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX user_signatures_content_sha256_index ON public.user_signatures USING btree (content_sha256);


--
-- Name: user_signatures_user_id_registered_at_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX user_signatures_user_id_registered_at_index ON public.user_signatures USING btree (user_id, registered_at);


--
-- Name: users_approved_at_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX users_approved_at_index ON public.users USING btree (approved_at);


--
-- Name: users_first_name_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX users_first_name_index ON public.users USING btree (first_name);


--
-- Name: users_last_name_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX users_last_name_index ON public.users USING btree (last_name);


--
-- Name: users_must_change_password_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX users_must_change_password_index ON public.users USING btree (must_change_password);


--
-- Name: users_status_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX users_status_index ON public.users USING btree (status);


--
-- Name: users_temporary_password_expires_at_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX users_temporary_password_expires_at_index ON public.users USING btree (temporary_password_expires_at);


--
-- Name: users_user_type_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX users_user_type_index ON public.users USING btree (user_type);


--
-- Name: audit_logs audit_logs_append_only; Type: TRIGGER; Schema: public; Owner: -
--

CREATE TRIGGER audit_logs_append_only BEFORE DELETE OR UPDATE ON public.audit_logs FOR EACH ROW EXECUTE FUNCTION public.prevent_audit_log_mutation();


--
-- Name: academic_terms academic_terms_academic_year_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.academic_terms
    ADD CONSTRAINT academic_terms_academic_year_id_foreign FOREIGN KEY (academic_year_id) REFERENCES public.academic_years(id) ON DELETE CASCADE;


--
-- Name: adviser_assignments adviser_assignments_adviser_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.adviser_assignments
    ADD CONSTRAINT adviser_assignments_adviser_id_foreign FOREIGN KEY (adviser_id) REFERENCES public.faculty_profiles(id) ON DELETE RESTRICT;


--
-- Name: adviser_assignments adviser_assignments_assigned_by_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.adviser_assignments
    ADD CONSTRAINT adviser_assignments_assigned_by_foreign FOREIGN KEY (assigned_by) REFERENCES public.users(id) ON DELETE SET NULL;


--
-- Name: adviser_assignments adviser_assignments_research_project_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.adviser_assignments
    ADD CONSTRAINT adviser_assignments_research_project_id_foreign FOREIGN KEY (research_project_id) REFERENCES public.research_projects(id) ON DELETE CASCADE;


--
-- Name: research_group_adviser_change_requests adviser_change_form_fk; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.research_group_adviser_change_requests
    ADD CONSTRAINT adviser_change_form_fk FOREIGN KEY (official_form_instance_id) REFERENCES public.official_form_instances(id) ON DELETE CASCADE;


--
-- Name: research_group_adviser_change_requests adviser_change_group_fk; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.research_group_adviser_change_requests
    ADD CONSTRAINT adviser_change_group_fk FOREIGN KEY (research_class_group_id) REFERENCES public.research_class_groups(id) ON DELETE CASCADE;


--
-- Name: research_group_adviser_change_requests adviser_change_previous_fk; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.research_group_adviser_change_requests
    ADD CONSTRAINT adviser_change_previous_fk FOREIGN KEY (previous_adviser_id) REFERENCES public.users(id) ON DELETE RESTRICT;


--
-- Name: research_group_adviser_change_requests adviser_change_requested_fk; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.research_group_adviser_change_requests
    ADD CONSTRAINT adviser_change_requested_fk FOREIGN KEY (requested_adviser_id) REFERENCES public.users(id) ON DELETE RESTRICT;


--
-- Name: research_group_adviser_change_requests adviser_change_requester_fk; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.research_group_adviser_change_requests
    ADD CONSTRAINT adviser_change_requester_fk FOREIGN KEY (requested_by) REFERENCES public.users(id) ON DELETE RESTRICT;


--
-- Name: research_group_adviser_change_requests adviser_change_reviewer_fk; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.research_group_adviser_change_requests
    ADD CONSTRAINT adviser_change_reviewer_fk FOREIGN KEY (reviewed_by) REFERENCES public.users(id) ON DELETE SET NULL;


--
-- Name: audit_logs audit_logs_user_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.audit_logs
    ADD CONSTRAINT audit_logs_user_id_foreign FOREIGN KEY (user_id) REFERENCES public.users(id) ON DELETE SET NULL;


--
-- Name: consultation_attendances consultation_attendances_consultation_record_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.consultation_attendances
    ADD CONSTRAINT consultation_attendances_consultation_record_id_foreign FOREIGN KEY (consultation_record_id) REFERENCES public.consultation_records(id) ON DELETE CASCADE;


--
-- Name: consultation_attendances consultation_attendances_student_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.consultation_attendances
    ADD CONSTRAINT consultation_attendances_student_id_foreign FOREIGN KEY (student_id) REFERENCES public.users(id) ON DELETE CASCADE;


--
-- Name: consultation_audits consultation_audits_actor_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.consultation_audits
    ADD CONSTRAINT consultation_audits_actor_id_foreign FOREIGN KEY (actor_id) REFERENCES public.users(id) ON DELETE CASCADE;


--
-- Name: consultation_audits consultation_audits_consultation_request_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.consultation_audits
    ADD CONSTRAINT consultation_audits_consultation_request_id_foreign FOREIGN KEY (consultation_request_id) REFERENCES public.consultation_requests(id) ON DELETE CASCADE;


--
-- Name: consultation_audits consultation_audits_research_class_group_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.consultation_audits
    ADD CONSTRAINT consultation_audits_research_class_group_id_foreign FOREIGN KEY (research_class_group_id) REFERENCES public.research_class_groups(id) ON DELETE CASCADE;


--
-- Name: consultation_records consultation_records_conducted_by_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.consultation_records
    ADD CONSTRAINT consultation_records_conducted_by_foreign FOREIGN KEY (conducted_by) REFERENCES public.users(id) ON DELETE CASCADE;


--
-- Name: consultation_records consultation_records_consultation_request_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.consultation_records
    ADD CONSTRAINT consultation_records_consultation_request_id_foreign FOREIGN KEY (consultation_request_id) REFERENCES public.consultation_requests(id) ON DELETE CASCADE;


--
-- Name: consultation_records consultation_records_research_class_group_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.consultation_records
    ADD CONSTRAINT consultation_records_research_class_group_id_foreign FOREIGN KEY (research_class_group_id) REFERENCES public.research_class_groups(id) ON DELETE CASCADE;


--
-- Name: consultation_records consultation_records_supersedes_record_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.consultation_records
    ADD CONSTRAINT consultation_records_supersedes_record_id_foreign FOREIGN KEY (supersedes_record_id) REFERENCES public.consultation_records(id) ON DELETE SET NULL;


--
-- Name: consultation_requests consultation_requests_adviser_assignment_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.consultation_requests
    ADD CONSTRAINT consultation_requests_adviser_assignment_id_foreign FOREIGN KEY (adviser_assignment_id) REFERENCES public.adviser_assignments(id) ON DELETE CASCADE;


--
-- Name: consultation_requests consultation_requests_assigned_adviser_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.consultation_requests
    ADD CONSTRAINT consultation_requests_assigned_adviser_id_foreign FOREIGN KEY (assigned_adviser_id) REFERENCES public.users(id) ON DELETE SET NULL;


--
-- Name: consultation_requests consultation_requests_cancelled_by_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.consultation_requests
    ADD CONSTRAINT consultation_requests_cancelled_by_foreign FOREIGN KEY (cancelled_by) REFERENCES public.users(id) ON DELETE SET NULL;


--
-- Name: consultation_requests consultation_requests_document_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.consultation_requests
    ADD CONSTRAINT consultation_requests_document_id_foreign FOREIGN KEY (document_id) REFERENCES public.documents(id) ON DELETE SET NULL;


--
-- Name: consultation_requests consultation_requests_requested_by_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.consultation_requests
    ADD CONSTRAINT consultation_requests_requested_by_foreign FOREIGN KEY (requested_by) REFERENCES public.users(id) ON DELETE CASCADE;


--
-- Name: consultation_requests consultation_requests_research_class_group_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.consultation_requests
    ADD CONSTRAINT consultation_requests_research_class_group_id_foreign FOREIGN KEY (research_class_group_id) REFERENCES public.research_class_groups(id) ON DELETE SET NULL;


--
-- Name: consultation_requests consultation_requests_research_project_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.consultation_requests
    ADD CONSTRAINT consultation_requests_research_project_id_foreign FOREIGN KEY (research_project_id) REFERENCES public.research_projects(id) ON DELETE CASCADE;


--
-- Name: consultation_requests consultation_requests_reviewed_by_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.consultation_requests
    ADD CONSTRAINT consultation_requests_reviewed_by_foreign FOREIGN KEY (reviewed_by) REFERENCES public.users(id) ON DELETE SET NULL;


--
-- Name: consultation_schedule_proposals consultation_schedule_proposals_consultation_request_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.consultation_schedule_proposals
    ADD CONSTRAINT consultation_schedule_proposals_consultation_request_id_foreign FOREIGN KEY (consultation_request_id) REFERENCES public.consultation_requests(id) ON DELETE CASCADE;


--
-- Name: consultation_schedule_proposals consultation_schedule_proposals_proposed_by_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.consultation_schedule_proposals
    ADD CONSTRAINT consultation_schedule_proposals_proposed_by_foreign FOREIGN KEY (proposed_by) REFERENCES public.users(id) ON DELETE CASCADE;


--
-- Name: consultation_schedule_proposals consultation_schedule_proposals_responded_by_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.consultation_schedule_proposals
    ADD CONSTRAINT consultation_schedule_proposals_responded_by_foreign FOREIGN KEY (responded_by) REFERENCES public.users(id) ON DELETE SET NULL;


--
-- Name: defense_evaluation_round_panelists defense_evaluation_round_panelists_defense_evaluation_round_id_; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.defense_evaluation_round_panelists
    ADD CONSTRAINT defense_evaluation_round_panelists_defense_evaluation_round_id_ FOREIGN KEY (defense_evaluation_round_id) REFERENCES public.defense_evaluation_rounds(id) ON DELETE CASCADE;


--
-- Name: defense_evaluation_round_panelists defense_evaluation_round_panelists_defense_panel_assignment_id_; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.defense_evaluation_round_panelists
    ADD CONSTRAINT defense_evaluation_round_panelists_defense_panel_assignment_id_ FOREIGN KEY (defense_panel_assignment_id) REFERENCES public.defense_panel_assignments(id) ON DELETE RESTRICT;


--
-- Name: defense_evaluation_round_panelists defense_evaluation_round_panelists_panelist_user_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.defense_evaluation_round_panelists
    ADD CONSTRAINT defense_evaluation_round_panelists_panelist_user_id_foreign FOREIGN KEY (panelist_user_id) REFERENCES public.users(id) ON DELETE RESTRICT;


--
-- Name: defense_evaluation_round_students defense_evaluation_round_students_defense_evaluation_round_id_f; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.defense_evaluation_round_students
    ADD CONSTRAINT defense_evaluation_round_students_defense_evaluation_round_id_f FOREIGN KEY (defense_evaluation_round_id) REFERENCES public.defense_evaluation_rounds(id) ON DELETE CASCADE;


--
-- Name: defense_evaluation_round_students defense_evaluation_round_students_group_member_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.defense_evaluation_round_students
    ADD CONSTRAINT defense_evaluation_round_students_group_member_id_foreign FOREIGN KEY (group_member_id) REFERENCES public.research_class_group_members(id) ON DELETE SET NULL;


--
-- Name: defense_evaluation_round_students defense_evaluation_round_students_student_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.defense_evaluation_round_students
    ADD CONSTRAINT defense_evaluation_round_students_student_id_foreign FOREIGN KEY (student_id) REFERENCES public.users(id) ON DELETE RESTRICT;


--
-- Name: defense_evaluation_rounds defense_evaluation_rounds_defense_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.defense_evaluation_rounds
    ADD CONSTRAINT defense_evaluation_rounds_defense_id_foreign FOREIGN KEY (defense_id) REFERENCES public.defenses(id) ON DELETE RESTRICT;


--
-- Name: defense_evaluation_rounds defense_evaluation_rounds_defense_schedule_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.defense_evaluation_rounds
    ADD CONSTRAINT defense_evaluation_rounds_defense_schedule_id_foreign FOREIGN KEY (defense_schedule_id) REFERENCES public.defense_schedules(id) ON DELETE RESTRICT;


--
-- Name: defense_evaluation_rounds defense_evaluation_rounds_opened_by_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.defense_evaluation_rounds
    ADD CONSTRAINT defense_evaluation_rounds_opened_by_foreign FOREIGN KEY (opened_by) REFERENCES public.users(id) ON DELETE RESTRICT;


--
-- Name: defense_evaluation_rounds defense_evaluation_rounds_released_by_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.defense_evaluation_rounds
    ADD CONSTRAINT defense_evaluation_rounds_released_by_foreign FOREIGN KEY (released_by) REFERENCES public.users(id) ON DELETE RESTRICT;


--
-- Name: defense_evaluation_rounds defense_evaluation_rounds_research_class_group_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.defense_evaluation_rounds
    ADD CONSTRAINT defense_evaluation_rounds_research_class_group_id_foreign FOREIGN KEY (research_class_group_id) REFERENCES public.research_class_groups(id) ON DELETE RESTRICT;


--
-- Name: defense_evaluation_rounds defense_evaluation_rounds_summary_signer_user_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.defense_evaluation_rounds
    ADD CONSTRAINT defense_evaluation_rounds_summary_signer_user_id_foreign FOREIGN KEY (summary_signer_user_id) REFERENCES public.users(id) ON DELETE RESTRICT;


--
-- Name: defense_evaluation_student_scores defense_evaluation_student_scores_defense_evaluation_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.defense_evaluation_student_scores
    ADD CONSTRAINT defense_evaluation_student_scores_defense_evaluation_id_foreign FOREIGN KEY (defense_evaluation_id) REFERENCES public.defense_evaluations(id) ON DELETE CASCADE;


--
-- Name: defense_evaluation_student_scores defense_evaluation_student_scores_round_student_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.defense_evaluation_student_scores
    ADD CONSTRAINT defense_evaluation_student_scores_round_student_id_foreign FOREIGN KEY (round_student_id) REFERENCES public.defense_evaluation_round_students(id) ON DELETE RESTRICT;


--
-- Name: defense_evaluation_student_scores defense_evaluation_student_scores_student_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.defense_evaluation_student_scores
    ADD CONSTRAINT defense_evaluation_student_scores_student_id_foreign FOREIGN KEY (student_id) REFERENCES public.users(id) ON DELETE RESTRICT;


--
-- Name: defense_evaluation_student_summaries defense_evaluation_student_summaries_defense_evaluation_summary; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.defense_evaluation_student_summaries
    ADD CONSTRAINT defense_evaluation_student_summaries_defense_evaluation_summary FOREIGN KEY (defense_evaluation_summary_id) REFERENCES public.defense_evaluation_summaries(id) ON DELETE CASCADE;


--
-- Name: defense_evaluation_student_summaries defense_evaluation_student_summaries_round_student_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.defense_evaluation_student_summaries
    ADD CONSTRAINT defense_evaluation_student_summaries_round_student_id_foreign FOREIGN KEY (round_student_id) REFERENCES public.defense_evaluation_round_students(id) ON DELETE RESTRICT;


--
-- Name: defense_evaluation_student_summaries defense_evaluation_student_summaries_student_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.defense_evaluation_student_summaries
    ADD CONSTRAINT defense_evaluation_student_summaries_student_id_foreign FOREIGN KEY (student_id) REFERENCES public.users(id) ON DELETE RESTRICT;


--
-- Name: defense_evaluation_summaries defense_evaluation_summaries_defense_evaluation_round_id_foreig; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.defense_evaluation_summaries
    ADD CONSTRAINT defense_evaluation_summaries_defense_evaluation_round_id_foreig FOREIGN KEY (defense_evaluation_round_id) REFERENCES public.defense_evaluation_rounds(id) ON DELETE RESTRICT;


--
-- Name: defense_evaluations defense_evaluations_defense_evaluation_round_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.defense_evaluations
    ADD CONSTRAINT defense_evaluations_defense_evaluation_round_id_foreign FOREIGN KEY (defense_evaluation_round_id) REFERENCES public.defense_evaluation_rounds(id) ON DELETE RESTRICT;


--
-- Name: defense_evaluations defense_evaluations_panelist_user_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.defense_evaluations
    ADD CONSTRAINT defense_evaluations_panelist_user_id_foreign FOREIGN KEY (panelist_user_id) REFERENCES public.users(id) ON DELETE RESTRICT;


--
-- Name: defense_evaluations defense_evaluations_round_panelist_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.defense_evaluations
    ADD CONSTRAINT defense_evaluations_round_panelist_id_foreign FOREIGN KEY (round_panelist_id) REFERENCES public.defense_evaluation_round_panelists(id) ON DELETE RESTRICT;


--
-- Name: defense_panel_assignments defense_panel_assignments_assigned_by_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.defense_panel_assignments
    ADD CONSTRAINT defense_panel_assignments_assigned_by_foreign FOREIGN KEY (assigned_by) REFERENCES public.users(id) ON DELETE RESTRICT;


--
-- Name: defense_panel_assignments defense_panel_assignments_defense_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.defense_panel_assignments
    ADD CONSTRAINT defense_panel_assignments_defense_id_foreign FOREIGN KEY (defense_id) REFERENCES public.defenses(id) ON DELETE RESTRICT;


--
-- Name: defense_panel_assignments defense_panel_assignments_user_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.defense_panel_assignments
    ADD CONSTRAINT defense_panel_assignments_user_id_foreign FOREIGN KEY (user_id) REFERENCES public.users(id) ON DELETE RESTRICT;


--
-- Name: defense_schedules defense_schedules_defense_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.defense_schedules
    ADD CONSTRAINT defense_schedules_defense_id_foreign FOREIGN KEY (defense_id) REFERENCES public.defenses(id) ON DELETE RESTRICT;


--
-- Name: defense_schedules defense_schedules_defense_session_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.defense_schedules
    ADD CONSTRAINT defense_schedules_defense_session_id_foreign FOREIGN KEY (defense_session_id) REFERENCES public.defense_sessions(id) ON DELETE SET NULL;


--
-- Name: defense_schedules defense_schedules_room_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.defense_schedules
    ADD CONSTRAINT defense_schedules_room_id_foreign FOREIGN KEY (room_id) REFERENCES public.defense_rooms(id) ON DELETE RESTRICT;


--
-- Name: defense_schedules defense_schedules_scheduled_by_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.defense_schedules
    ADD CONSTRAINT defense_schedules_scheduled_by_foreign FOREIGN KEY (scheduled_by) REFERENCES public.users(id) ON DELETE RESTRICT;


--
-- Name: defense_schedules defense_schedules_supersedes_schedule_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.defense_schedules
    ADD CONSTRAINT defense_schedules_supersedes_schedule_id_foreign FOREIGN KEY (supersedes_schedule_id) REFERENCES public.defense_schedules(id) ON DELETE RESTRICT;


--
-- Name: defense_sessions defense_sessions_research_class_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.defense_sessions
    ADD CONSTRAINT defense_sessions_research_class_id_foreign FOREIGN KEY (research_class_id) REFERENCES public.research_classes(id) ON DELETE SET NULL;


--
-- Name: defense_sessions defense_sessions_room_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.defense_sessions
    ADD CONSTRAINT defense_sessions_room_id_foreign FOREIGN KEY (room_id) REFERENCES public.defense_rooms(id) ON DELETE CASCADE;


--
-- Name: defense_sessions defense_sessions_scheduled_by_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.defense_sessions
    ADD CONSTRAINT defense_sessions_scheduled_by_foreign FOREIGN KEY (scheduled_by) REFERENCES public.users(id) ON DELETE SET NULL;


--
-- Name: defenses defenses_completed_by_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.defenses
    ADD CONSTRAINT defenses_completed_by_foreign FOREIGN KEY (completed_by) REFERENCES public.users(id) ON DELETE SET NULL;


--
-- Name: defenses defenses_created_by_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.defenses
    ADD CONSTRAINT defenses_created_by_foreign FOREIGN KEY (created_by) REFERENCES public.users(id) ON DELETE RESTRICT;


--
-- Name: defenses defenses_current_schedule_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.defenses
    ADD CONSTRAINT defenses_current_schedule_id_foreign FOREIGN KEY (current_schedule_id) REFERENCES public.defense_schedules(id) ON DELETE SET NULL;


--
-- Name: defenses defenses_research_class_group_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.defenses
    ADD CONSTRAINT defenses_research_class_group_id_foreign FOREIGN KEY (research_class_group_id) REFERENCES public.research_class_groups(id) ON DELETE RESTRICT;


--
-- Name: departments departments_college_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.departments
    ADD CONSTRAINT departments_college_id_foreign FOREIGN KEY (college_id) REFERENCES public.colleges(id) ON DELETE RESTRICT;


--
-- Name: document_access_audits document_access_audits_document_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.document_access_audits
    ADD CONSTRAINT document_access_audits_document_id_foreign FOREIGN KEY (document_id) REFERENCES public.documents(id) ON DELETE RESTRICT;


--
-- Name: document_access_audits document_access_audits_user_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.document_access_audits
    ADD CONSTRAINT document_access_audits_user_id_foreign FOREIGN KEY (user_id) REFERENCES public.users(id) ON DELETE SET NULL;


--
-- Name: document_review_audits document_review_audits_document_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.document_review_audits
    ADD CONSTRAINT document_review_audits_document_id_foreign FOREIGN KEY (document_id) REFERENCES public.documents(id) ON DELETE CASCADE;


--
-- Name: document_review_audits document_review_audits_reviewer_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.document_review_audits
    ADD CONSTRAINT document_review_audits_reviewer_id_foreign FOREIGN KEY (reviewer_id) REFERENCES public.users(id) ON DELETE RESTRICT;


--
-- Name: document_review_audits document_review_audits_student_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.document_review_audits
    ADD CONSTRAINT document_review_audits_student_id_foreign FOREIGN KEY (student_id) REFERENCES public.users(id) ON DELETE CASCADE;


--
-- Name: document_review_comments document_review_comments_author_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.document_review_comments
    ADD CONSTRAINT document_review_comments_author_id_foreign FOREIGN KEY (author_id) REFERENCES public.users(id) ON DELETE RESTRICT;


--
-- Name: document_review_comments document_review_comments_document_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.document_review_comments
    ADD CONSTRAINT document_review_comments_document_id_foreign FOREIGN KEY (document_id) REFERENCES public.documents(id) ON DELETE CASCADE;


--
-- Name: document_review_comments document_review_comments_parent_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.document_review_comments
    ADD CONSTRAINT document_review_comments_parent_id_foreign FOREIGN KEY (parent_id) REFERENCES public.document_review_comments(id) ON DELETE CASCADE;


--
-- Name: document_review_comments document_review_comments_resolved_by_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.document_review_comments
    ADD CONSTRAINT document_review_comments_resolved_by_foreign FOREIGN KEY (resolved_by) REFERENCES public.users(id) ON DELETE SET NULL;


--
-- Name: document_reviews document_reviews_document_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.document_reviews
    ADD CONSTRAINT document_reviews_document_id_foreign FOREIGN KEY (document_id) REFERENCES public.documents(id) ON DELETE CASCADE;


--
-- Name: document_reviews document_reviews_reviewer_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.document_reviews
    ADD CONSTRAINT document_reviews_reviewer_id_foreign FOREIGN KEY (reviewer_id) REFERENCES public.users(id) ON DELETE RESTRICT;


--
-- Name: document_reviews document_reviews_supersedes_review_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.document_reviews
    ADD CONSTRAINT document_reviews_supersedes_review_id_foreign FOREIGN KEY (supersedes_review_id) REFERENCES public.document_reviews(id) ON DELETE SET NULL;


--
-- Name: document_upload_audits document_upload_audits_document_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.document_upload_audits
    ADD CONSTRAINT document_upload_audits_document_id_foreign FOREIGN KEY (document_id) REFERENCES public.documents(id) ON DELETE SET NULL;


--
-- Name: document_upload_audits document_upload_audits_research_class_group_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.document_upload_audits
    ADD CONSTRAINT document_upload_audits_research_class_group_id_foreign FOREIGN KEY (research_class_group_id) REFERENCES public.research_class_groups(id) ON DELETE SET NULL;


--
-- Name: document_upload_audits document_upload_audits_user_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.document_upload_audits
    ADD CONSTRAINT document_upload_audits_user_id_foreign FOREIGN KEY (user_id) REFERENCES public.users(id) ON DELETE SET NULL;


--
-- Name: documents documents_research_class_group_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.documents
    ADD CONSTRAINT documents_research_class_group_id_foreign FOREIGN KEY (research_class_group_id) REFERENCES public.research_class_groups(id) ON DELETE CASCADE;


--
-- Name: documents documents_revision_request_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.documents
    ADD CONSTRAINT documents_revision_request_id_foreign FOREIGN KEY (revision_request_id) REFERENCES public.revision_requests(id) ON DELETE SET NULL;


--
-- Name: documents documents_user_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.documents
    ADD CONSTRAINT documents_user_id_foreign FOREIGN KEY (user_id) REFERENCES public.users(id) ON DELETE CASCADE;


--
-- Name: faculty_profile_departments faculty_profile_departments_department_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.faculty_profile_departments
    ADD CONSTRAINT faculty_profile_departments_department_id_foreign FOREIGN KEY (department_id) REFERENCES public.departments(id) ON DELETE RESTRICT;


--
-- Name: faculty_profile_departments faculty_profile_departments_faculty_profile_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.faculty_profile_departments
    ADD CONSTRAINT faculty_profile_departments_faculty_profile_id_foreign FOREIGN KEY (faculty_profile_id) REFERENCES public.faculty_profiles(id) ON DELETE CASCADE;


--
-- Name: faculty_profiles faculty_profiles_department_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.faculty_profiles
    ADD CONSTRAINT faculty_profiles_department_id_foreign FOREIGN KEY (department_id) REFERENCES public.departments(id) ON DELETE RESTRICT;


--
-- Name: faculty_profiles faculty_profiles_user_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.faculty_profiles
    ADD CONSTRAINT faculty_profiles_user_id_foreign FOREIGN KEY (user_id) REFERENCES public.users(id) ON DELETE CASCADE;


--
-- Name: milestone_evidences milestone_evidences_linked_by_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.milestone_evidences
    ADD CONSTRAINT milestone_evidences_linked_by_foreign FOREIGN KEY (linked_by) REFERENCES public.users(id) ON DELETE SET NULL;


--
-- Name: milestone_evidences milestone_evidences_research_group_milestone_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.milestone_evidences
    ADD CONSTRAINT milestone_evidences_research_group_milestone_id_foreign FOREIGN KEY (research_group_milestone_id) REFERENCES public.research_group_milestones(id) ON DELETE RESTRICT;


--
-- Name: model_has_permissions model_has_permissions_permission_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.model_has_permissions
    ADD CONSTRAINT model_has_permissions_permission_id_foreign FOREIGN KEY (permission_id) REFERENCES public.permissions(id) ON DELETE CASCADE;


--
-- Name: model_has_roles model_has_roles_role_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.model_has_roles
    ADD CONSTRAINT model_has_roles_role_id_foreign FOREIGN KEY (role_id) REFERENCES public.roles(id) ON DELETE CASCADE;


--
-- Name: official_form_actor_assignments official_form_actor_assignments_assigned_by_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.official_form_actor_assignments
    ADD CONSTRAINT official_form_actor_assignments_assigned_by_foreign FOREIGN KEY (assigned_by) REFERENCES public.users(id) ON DELETE SET NULL;


--
-- Name: official_form_actor_assignments official_form_actor_assignments_official_form_instance_id_forei; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.official_form_actor_assignments
    ADD CONSTRAINT official_form_actor_assignments_official_form_instance_id_forei FOREIGN KEY (official_form_instance_id) REFERENCES public.official_form_instances(id) ON DELETE RESTRICT;


--
-- Name: official_form_actor_assignments official_form_actor_assignments_user_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.official_form_actor_assignments
    ADD CONSTRAINT official_form_actor_assignments_user_id_foreign FOREIGN KEY (user_id) REFERENCES public.users(id) ON DELETE RESTRICT;


--
-- Name: official_form_instances official_form_instances_current_version_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.official_form_instances
    ADD CONSTRAINT official_form_instances_current_version_id_foreign FOREIGN KEY (current_version_id) REFERENCES public.official_form_versions(id) ON DELETE SET NULL;


--
-- Name: official_form_instances official_form_instances_defense_evaluation_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.official_form_instances
    ADD CONSTRAINT official_form_instances_defense_evaluation_id_foreign FOREIGN KEY (defense_evaluation_id) REFERENCES public.defense_evaluations(id) ON DELETE RESTRICT;


--
-- Name: official_form_instances official_form_instances_initiated_by_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.official_form_instances
    ADD CONSTRAINT official_form_instances_initiated_by_foreign FOREIGN KEY (initiated_by) REFERENCES public.users(id) ON DELETE RESTRICT;


--
-- Name: official_form_instances official_form_instances_official_form_definition_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.official_form_instances
    ADD CONSTRAINT official_form_instances_official_form_definition_id_foreign FOREIGN KEY (official_form_definition_id) REFERENCES public.official_form_definitions(id) ON DELETE RESTRICT;


--
-- Name: official_form_instances official_form_instances_research_class_group_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.official_form_instances
    ADD CONSTRAINT official_form_instances_research_class_group_id_foreign FOREIGN KEY (research_class_group_id) REFERENCES public.research_class_groups(id) ON DELETE SET NULL;


--
-- Name: official_form_instances official_form_instances_research_class_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.official_form_instances
    ADD CONSTRAINT official_form_instances_research_class_id_foreign FOREIGN KEY (research_class_id) REFERENCES public.research_classes(id) ON DELETE SET NULL;


--
-- Name: official_form_signature_verifications official_form_signature_verifications_official_for_14624f41b4d2; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.official_form_signature_verifications
    ADD CONSTRAINT official_form_signature_verifications_official_for_14624f41b4d2 FOREIGN KEY (official_form_signature_id) REFERENCES public.official_form_signatures(id) ON DELETE CASCADE;


--
-- Name: official_form_signatures official_form_signatures_official_form_instance_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.official_form_signatures
    ADD CONSTRAINT official_form_signatures_official_form_instance_id_foreign FOREIGN KEY (official_form_instance_id) REFERENCES public.official_form_instances(id) ON DELETE RESTRICT;


--
-- Name: official_form_signatures official_form_signatures_official_form_version_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.official_form_signatures
    ADD CONSTRAINT official_form_signatures_official_form_version_id_foreign FOREIGN KEY (official_form_version_id) REFERENCES public.official_form_versions(id) ON DELETE RESTRICT;


--
-- Name: official_form_signatures official_form_signatures_signer_user_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.official_form_signatures
    ADD CONSTRAINT official_form_signatures_signer_user_id_foreign FOREIGN KEY (signer_user_id) REFERENCES public.users(id) ON DELETE RESTRICT;


--
-- Name: official_form_signatures official_form_signatures_user_signature_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.official_form_signatures
    ADD CONSTRAINT official_form_signatures_user_signature_id_foreign FOREIGN KEY (user_signature_id) REFERENCES public.user_signatures(id) ON DELETE SET NULL;


--
-- Name: official_form_verifications official_form_verifications_official_form_version_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.official_form_verifications
    ADD CONSTRAINT official_form_verifications_official_form_version_id_foreign FOREIGN KEY (official_form_version_id) REFERENCES public.official_form_versions(id) ON DELETE RESTRICT;


--
-- Name: official_form_versions official_form_versions_created_by_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.official_form_versions
    ADD CONSTRAINT official_form_versions_created_by_foreign FOREIGN KEY (created_by) REFERENCES public.users(id) ON DELETE RESTRICT;


--
-- Name: official_form_versions official_form_versions_official_form_instance_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.official_form_versions
    ADD CONSTRAINT official_form_versions_official_form_instance_id_foreign FOREIGN KEY (official_form_instance_id) REFERENCES public.official_form_instances(id) ON DELETE RESTRICT;


--
-- Name: official_form_versions official_form_versions_supersedes_version_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.official_form_versions
    ADD CONSTRAINT official_form_versions_supersedes_version_id_foreign FOREIGN KEY (supersedes_version_id) REFERENCES public.official_form_versions(id) ON DELETE SET NULL;


--
-- Name: programs programs_department_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.programs
    ADD CONSTRAINT programs_department_id_foreign FOREIGN KEY (department_id) REFERENCES public.departments(id) ON DELETE RESTRICT;


--
-- Name: research_class_actor_assignments research_class_actor_assignments_assigned_by_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.research_class_actor_assignments
    ADD CONSTRAINT research_class_actor_assignments_assigned_by_foreign FOREIGN KEY (assigned_by) REFERENCES public.users(id) ON DELETE SET NULL;


--
-- Name: research_class_actor_assignments research_class_actor_assignments_research_class_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.research_class_actor_assignments
    ADD CONSTRAINT research_class_actor_assignments_research_class_id_foreign FOREIGN KEY (research_class_id) REFERENCES public.research_classes(id) ON DELETE CASCADE;


--
-- Name: research_class_actor_assignments research_class_actor_assignments_user_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.research_class_actor_assignments
    ADD CONSTRAINT research_class_actor_assignments_user_id_foreign FOREIGN KEY (user_id) REFERENCES public.users(id) ON DELETE RESTRICT;


--
-- Name: research_class_enrollments research_class_enrollments_research_class_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.research_class_enrollments
    ADD CONSTRAINT research_class_enrollments_research_class_id_foreign FOREIGN KEY (research_class_id) REFERENCES public.research_classes(id) ON DELETE CASCADE;


--
-- Name: research_class_enrollments research_class_enrollments_reviewed_by_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.research_class_enrollments
    ADD CONSTRAINT research_class_enrollments_reviewed_by_foreign FOREIGN KEY (reviewed_by) REFERENCES public.users(id) ON DELETE SET NULL;


--
-- Name: research_class_enrollments research_class_enrollments_student_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.research_class_enrollments
    ADD CONSTRAINT research_class_enrollments_student_id_foreign FOREIGN KEY (student_id) REFERENCES public.users(id) ON DELETE CASCADE;


--
-- Name: research_class_group_adviser_histories research_class_group_adviser_histories_adviser_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.research_class_group_adviser_histories
    ADD CONSTRAINT research_class_group_adviser_histories_adviser_id_foreign FOREIGN KEY (adviser_id) REFERENCES public.users(id) ON DELETE RESTRICT;


--
-- Name: research_class_group_adviser_histories research_class_group_adviser_histories_assigned_by_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.research_class_group_adviser_histories
    ADD CONSTRAINT research_class_group_adviser_histories_assigned_by_foreign FOREIGN KEY (assigned_by) REFERENCES public.users(id) ON DELETE RESTRICT;


--
-- Name: research_class_group_adviser_histories research_class_group_adviser_histories_ended_by_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.research_class_group_adviser_histories
    ADD CONSTRAINT research_class_group_adviser_histories_ended_by_foreign FOREIGN KEY (ended_by) REFERENCES public.users(id) ON DELETE SET NULL;


--
-- Name: research_class_group_adviser_histories research_class_group_adviser_histories_research_class_group_id_; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.research_class_group_adviser_histories
    ADD CONSTRAINT research_class_group_adviser_histories_research_class_group_id_ FOREIGN KEY (research_class_group_id) REFERENCES public.research_class_groups(id) ON DELETE CASCADE;


--
-- Name: research_class_group_adviser_requests research_class_group_adviser_requests_adviser_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.research_class_group_adviser_requests
    ADD CONSTRAINT research_class_group_adviser_requests_adviser_id_foreign FOREIGN KEY (adviser_id) REFERENCES public.users(id) ON DELETE RESTRICT;


--
-- Name: research_class_group_adviser_requests research_class_group_adviser_requests_requested_by_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.research_class_group_adviser_requests
    ADD CONSTRAINT research_class_group_adviser_requests_requested_by_foreign FOREIGN KEY (requested_by) REFERENCES public.users(id) ON DELETE RESTRICT;


--
-- Name: research_class_group_adviser_requests research_class_group_adviser_requests_research_class_group_id_f; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.research_class_group_adviser_requests
    ADD CONSTRAINT research_class_group_adviser_requests_research_class_group_id_f FOREIGN KEY (research_class_group_id) REFERENCES public.research_class_groups(id) ON DELETE CASCADE;


--
-- Name: research_class_group_member_histories research_class_group_member_histories_assigned_by_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.research_class_group_member_histories
    ADD CONSTRAINT research_class_group_member_histories_assigned_by_foreign FOREIGN KEY (assigned_by) REFERENCES public.users(id) ON DELETE SET NULL;


--
-- Name: research_class_group_member_histories research_class_group_member_histories_research_class_enrollment; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.research_class_group_member_histories
    ADD CONSTRAINT research_class_group_member_histories_research_class_enrollment FOREIGN KEY (research_class_enrollment_id) REFERENCES public.research_class_enrollments(id) ON DELETE SET NULL;


--
-- Name: research_class_group_member_histories research_class_group_member_histories_research_class_group_id_f; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.research_class_group_member_histories
    ADD CONSTRAINT research_class_group_member_histories_research_class_group_id_f FOREIGN KEY (research_class_group_id) REFERENCES public.research_class_groups(id) ON DELETE RESTRICT;


--
-- Name: research_class_group_member_histories research_class_group_member_histories_research_class_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.research_class_group_member_histories
    ADD CONSTRAINT research_class_group_member_histories_research_class_id_foreign FOREIGN KEY (research_class_id) REFERENCES public.research_classes(id) ON DELETE RESTRICT;


--
-- Name: research_class_group_member_histories research_class_group_member_histories_student_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.research_class_group_member_histories
    ADD CONSTRAINT research_class_group_member_histories_student_id_foreign FOREIGN KEY (student_id) REFERENCES public.users(id) ON DELETE RESTRICT;


--
-- Name: research_class_group_members research_class_group_members_assigned_by_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.research_class_group_members
    ADD CONSTRAINT research_class_group_members_assigned_by_foreign FOREIGN KEY (assigned_by) REFERENCES public.users(id) ON DELETE RESTRICT;


--
-- Name: research_class_group_members research_class_group_members_research_class_enrollment_id_forei; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.research_class_group_members
    ADD CONSTRAINT research_class_group_members_research_class_enrollment_id_forei FOREIGN KEY (research_class_enrollment_id) REFERENCES public.research_class_enrollments(id) ON DELETE CASCADE;


--
-- Name: research_class_group_members research_class_group_members_research_class_group_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.research_class_group_members
    ADD CONSTRAINT research_class_group_members_research_class_group_id_foreign FOREIGN KEY (research_class_group_id) REFERENCES public.research_class_groups(id) ON DELETE CASCADE;


--
-- Name: research_class_group_members research_class_group_members_research_class_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.research_class_group_members
    ADD CONSTRAINT research_class_group_members_research_class_id_foreign FOREIGN KEY (research_class_id) REFERENCES public.research_classes(id) ON DELETE CASCADE;


--
-- Name: research_class_group_members research_class_group_members_student_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.research_class_group_members
    ADD CONSTRAINT research_class_group_members_student_id_foreign FOREIGN KEY (student_id) REFERENCES public.users(id) ON DELETE CASCADE;


--
-- Name: research_class_groups research_class_groups_adviser_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.research_class_groups
    ADD CONSTRAINT research_class_groups_adviser_id_foreign FOREIGN KEY (adviser_id) REFERENCES public.users(id) ON DELETE SET NULL;


--
-- Name: research_class_groups research_class_groups_created_by_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.research_class_groups
    ADD CONSTRAINT research_class_groups_created_by_foreign FOREIGN KEY (created_by) REFERENCES public.users(id) ON DELETE RESTRICT;


--
-- Name: research_class_groups research_class_groups_leader_student_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.research_class_groups
    ADD CONSTRAINT research_class_groups_leader_student_id_foreign FOREIGN KEY (leader_student_id) REFERENCES public.users(id) ON DELETE SET NULL;


--
-- Name: research_class_groups research_class_groups_research_class_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.research_class_groups
    ADD CONSTRAINT research_class_groups_research_class_id_foreign FOREIGN KEY (research_class_id) REFERENCES public.research_classes(id) ON DELETE CASCADE;


--
-- Name: research_class_groups research_class_groups_research_group_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.research_class_groups
    ADD CONSTRAINT research_class_groups_research_group_id_foreign FOREIGN KEY (research_group_id) REFERENCES public.research_groups(id) ON DELETE SET NULL;


--
-- Name: research_class_panel_committees research_class_panel_committees_chairperson_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.research_class_panel_committees
    ADD CONSTRAINT research_class_panel_committees_chairperson_id_foreign FOREIGN KEY (chairperson_id) REFERENCES public.users(id) ON DELETE CASCADE;


--
-- Name: research_class_panel_committees research_class_panel_committees_created_by_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.research_class_panel_committees
    ADD CONSTRAINT research_class_panel_committees_created_by_foreign FOREIGN KEY (created_by) REFERENCES public.users(id) ON DELETE SET NULL;


--
-- Name: research_class_panel_committees research_class_panel_committees_research_class_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.research_class_panel_committees
    ADD CONSTRAINT research_class_panel_committees_research_class_id_foreign FOREIGN KEY (research_class_id) REFERENCES public.research_classes(id) ON DELETE CASCADE;


--
-- Name: research_class_panel_committees research_class_panel_committees_updated_by_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.research_class_panel_committees
    ADD CONSTRAINT research_class_panel_committees_updated_by_foreign FOREIGN KEY (updated_by) REFERENCES public.users(id) ON DELETE SET NULL;


--
-- Name: research_class_panel_members research_class_panel_members_committee_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.research_class_panel_members
    ADD CONSTRAINT research_class_panel_members_committee_id_foreign FOREIGN KEY (committee_id) REFERENCES public.research_class_panel_committees(id) ON DELETE CASCADE;


--
-- Name: research_class_panel_members research_class_panel_members_user_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.research_class_panel_members
    ADD CONSTRAINT research_class_panel_members_user_id_foreign FOREIGN KEY (user_id) REFERENCES public.users(id) ON DELETE CASCADE;


--
-- Name: research_classes research_classes_adviser_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.research_classes
    ADD CONSTRAINT research_classes_adviser_id_foreign FOREIGN KEY (facilitator_id) REFERENCES public.users(id) ON DELETE CASCADE;


--
-- Name: research_group_members research_group_members_research_group_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.research_group_members
    ADD CONSTRAINT research_group_members_research_group_id_foreign FOREIGN KEY (research_group_id) REFERENCES public.research_groups(id) ON DELETE CASCADE;


--
-- Name: research_group_members research_group_members_student_profile_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.research_group_members
    ADD CONSTRAINT research_group_members_student_profile_id_foreign FOREIGN KEY (student_profile_id) REFERENCES public.student_profiles(id) ON DELETE CASCADE;


--
-- Name: research_group_milestone_events research_group_milestone_events_actor_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.research_group_milestone_events
    ADD CONSTRAINT research_group_milestone_events_actor_id_foreign FOREIGN KEY (actor_id) REFERENCES public.users(id) ON DELETE SET NULL;


--
-- Name: research_group_milestone_events research_group_milestone_events_research_group_milestone_id_for; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.research_group_milestone_events
    ADD CONSTRAINT research_group_milestone_events_research_group_milestone_id_for FOREIGN KEY (research_group_milestone_id) REFERENCES public.research_group_milestones(id) ON DELETE RESTRICT;


--
-- Name: research_group_milestones research_group_milestones_completed_by_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.research_group_milestones
    ADD CONSTRAINT research_group_milestones_completed_by_foreign FOREIGN KEY (completed_by) REFERENCES public.users(id) ON DELETE SET NULL;


--
-- Name: research_group_milestones research_group_milestones_milestone_definition_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.research_group_milestones
    ADD CONSTRAINT research_group_milestones_milestone_definition_id_foreign FOREIGN KEY (milestone_definition_id) REFERENCES public.milestone_definitions(id) ON DELETE RESTRICT;


--
-- Name: research_group_milestones research_group_milestones_research_class_group_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.research_group_milestones
    ADD CONSTRAINT research_group_milestones_research_class_group_id_foreign FOREIGN KEY (research_class_group_id) REFERENCES public.research_class_groups(id) ON DELETE RESTRICT;


--
-- Name: research_group_milestones research_group_milestones_started_by_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.research_group_milestones
    ADD CONSTRAINT research_group_milestones_started_by_foreign FOREIGN KEY (started_by) REFERENCES public.users(id) ON DELETE SET NULL;


--
-- Name: research_group_milestones research_group_milestones_updated_by_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.research_group_milestones
    ADD CONSTRAINT research_group_milestones_updated_by_foreign FOREIGN KEY (updated_by) REFERENCES public.users(id) ON DELETE SET NULL;


--
-- Name: research_group_panel_committees research_group_panel_committees_chairperson_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.research_group_panel_committees
    ADD CONSTRAINT research_group_panel_committees_chairperson_id_foreign FOREIGN KEY (chairperson_id) REFERENCES public.users(id) ON DELETE CASCADE;


--
-- Name: research_group_panel_committees research_group_panel_committees_created_by_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.research_group_panel_committees
    ADD CONSTRAINT research_group_panel_committees_created_by_foreign FOREIGN KEY (created_by) REFERENCES public.users(id) ON DELETE SET NULL;


--
-- Name: research_group_panel_committees research_group_panel_committees_research_class_group_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.research_group_panel_committees
    ADD CONSTRAINT research_group_panel_committees_research_class_group_id_foreign FOREIGN KEY (research_class_group_id) REFERENCES public.research_class_groups(id) ON DELETE CASCADE;


--
-- Name: research_group_panel_committees research_group_panel_committees_updated_by_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.research_group_panel_committees
    ADD CONSTRAINT research_group_panel_committees_updated_by_foreign FOREIGN KEY (updated_by) REFERENCES public.users(id) ON DELETE SET NULL;


--
-- Name: research_group_panel_members research_group_panel_members_committee_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.research_group_panel_members
    ADD CONSTRAINT research_group_panel_members_committee_id_foreign FOREIGN KEY (committee_id) REFERENCES public.research_group_panel_committees(id) ON DELETE CASCADE;


--
-- Name: research_group_panel_members research_group_panel_members_user_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.research_group_panel_members
    ADD CONSTRAINT research_group_panel_members_user_id_foreign FOREIGN KEY (user_id) REFERENCES public.users(id) ON DELETE CASCADE;


--
-- Name: research_groups research_groups_academic_term_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.research_groups
    ADD CONSTRAINT research_groups_academic_term_id_foreign FOREIGN KEY (academic_term_id) REFERENCES public.academic_terms(id) ON DELETE RESTRICT;


--
-- Name: research_groups research_groups_created_by_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.research_groups
    ADD CONSTRAINT research_groups_created_by_foreign FOREIGN KEY (created_by) REFERENCES public.users(id) ON DELETE SET NULL;


--
-- Name: research_groups research_groups_program_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.research_groups
    ADD CONSTRAINT research_groups_program_id_foreign FOREIGN KEY (program_id) REFERENCES public.programs(id) ON DELETE RESTRICT;


--
-- Name: research_project_title_histories research_project_title_histories_changed_by_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.research_project_title_histories
    ADD CONSTRAINT research_project_title_histories_changed_by_foreign FOREIGN KEY (changed_by) REFERENCES public.users(id) ON DELETE RESTRICT;


--
-- Name: research_project_title_histories research_project_title_histories_research_project_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.research_project_title_histories
    ADD CONSTRAINT research_project_title_histories_research_project_id_foreign FOREIGN KEY (research_project_id) REFERENCES public.research_projects(id) ON DELETE CASCADE;


--
-- Name: research_projects research_projects_created_by_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.research_projects
    ADD CONSTRAINT research_projects_created_by_foreign FOREIGN KEY (created_by) REFERENCES public.users(id) ON DELETE SET NULL;


--
-- Name: research_projects research_projects_research_group_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.research_projects
    ADD CONSTRAINT research_projects_research_group_id_foreign FOREIGN KEY (research_group_id) REFERENCES public.research_groups(id) ON DELETE RESTRICT;


--
-- Name: research_proposals research_proposals_document_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.research_proposals
    ADD CONSTRAINT research_proposals_document_id_foreign FOREIGN KEY (document_id) REFERENCES public.documents(id) ON DELETE SET NULL;


--
-- Name: research_proposals research_proposals_reviewed_by_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.research_proposals
    ADD CONSTRAINT research_proposals_reviewed_by_foreign FOREIGN KEY (reviewed_by) REFERENCES public.users(id) ON DELETE SET NULL;


--
-- Name: research_proposals research_proposals_submitted_by_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.research_proposals
    ADD CONSTRAINT research_proposals_submitted_by_foreign FOREIGN KEY (submitted_by) REFERENCES public.users(id) ON DELETE CASCADE;


--
-- Name: revision_request_events revision_request_events_actor_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.revision_request_events
    ADD CONSTRAINT revision_request_events_actor_id_foreign FOREIGN KEY (actor_id) REFERENCES public.users(id) ON DELETE RESTRICT;


--
-- Name: revision_request_events revision_request_events_document_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.revision_request_events
    ADD CONSTRAINT revision_request_events_document_id_foreign FOREIGN KEY (document_id) REFERENCES public.documents(id) ON DELETE SET NULL;


--
-- Name: revision_request_events revision_request_events_revision_request_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.revision_request_events
    ADD CONSTRAINT revision_request_events_revision_request_id_foreign FOREIGN KEY (revision_request_id) REFERENCES public.revision_requests(id) ON DELETE CASCADE;


--
-- Name: revision_requests revision_requests_assigned_to_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.revision_requests
    ADD CONSTRAINT revision_requests_assigned_to_foreign FOREIGN KEY (assigned_to) REFERENCES public.users(id) ON DELETE CASCADE;


--
-- Name: revision_requests revision_requests_document_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.revision_requests
    ADD CONSTRAINT revision_requests_document_id_foreign FOREIGN KEY (document_id) REFERENCES public.documents(id) ON DELETE SET NULL;


--
-- Name: revision_requests revision_requests_requested_by_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.revision_requests
    ADD CONSTRAINT revision_requests_requested_by_foreign FOREIGN KEY (requested_by) REFERENCES public.users(id) ON DELETE RESTRICT;


--
-- Name: revision_requests revision_requests_research_class_group_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.revision_requests
    ADD CONSTRAINT revision_requests_research_class_group_id_foreign FOREIGN KEY (research_class_group_id) REFERENCES public.research_class_groups(id) ON DELETE RESTRICT;


--
-- Name: revision_requests revision_requests_source_document_review_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.revision_requests
    ADD CONSTRAINT revision_requests_source_document_review_id_foreign FOREIGN KEY (source_document_review_id) REFERENCES public.document_reviews(id) ON DELETE SET NULL;


--
-- Name: revision_requests revision_requests_submitted_document_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.revision_requests
    ADD CONSTRAINT revision_requests_submitted_document_id_foreign FOREIGN KEY (submitted_document_id) REFERENCES public.documents(id) ON DELETE SET NULL;


--
-- Name: role_has_permissions role_has_permissions_permission_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.role_has_permissions
    ADD CONSTRAINT role_has_permissions_permission_id_foreign FOREIGN KEY (permission_id) REFERENCES public.permissions(id) ON DELETE CASCADE;


--
-- Name: role_has_permissions role_has_permissions_role_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.role_has_permissions
    ADD CONSTRAINT role_has_permissions_role_id_foreign FOREIGN KEY (role_id) REFERENCES public.roles(id) ON DELETE CASCADE;


--
-- Name: signature_audits signature_audits_user_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.signature_audits
    ADD CONSTRAINT signature_audits_user_id_foreign FOREIGN KEY (user_id) REFERENCES public.users(id) ON DELETE SET NULL;


--
-- Name: signature_audits signature_audits_user_signature_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.signature_audits
    ADD CONSTRAINT signature_audits_user_signature_id_foreign FOREIGN KEY (user_signature_id) REFERENCES public.user_signatures(id) ON DELETE SET NULL;


--
-- Name: student_profiles student_profiles_program_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.student_profiles
    ADD CONSTRAINT student_profiles_program_id_foreign FOREIGN KEY (program_id) REFERENCES public.programs(id) ON DELETE RESTRICT;


--
-- Name: student_profiles student_profiles_user_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.student_profiles
    ADD CONSTRAINT student_profiles_user_id_foreign FOREIGN KEY (user_id) REFERENCES public.users(id) ON DELETE CASCADE;


--
-- Name: system_backup_settings system_backup_settings_updated_by_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.system_backup_settings
    ADD CONSTRAINT system_backup_settings_updated_by_foreign FOREIGN KEY (updated_by) REFERENCES public.users(id) ON DELETE SET NULL;


--
-- Name: system_backups system_backups_restored_by_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.system_backups
    ADD CONSTRAINT system_backups_restored_by_foreign FOREIGN KEY (restored_by) REFERENCES public.users(id) ON DELETE SET NULL;


--
-- Name: system_backups system_backups_triggered_by_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.system_backups
    ADD CONSTRAINT system_backups_triggered_by_foreign FOREIGN KEY (triggered_by) REFERENCES public.users(id) ON DELETE SET NULL;


--
-- Name: system_backups system_backups_verified_by_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.system_backups
    ADD CONSTRAINT system_backups_verified_by_foreign FOREIGN KEY (verified_by) REFERENCES public.users(id) ON DELETE SET NULL;


--
-- Name: system_settings system_settings_updated_by_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.system_settings
    ADD CONSTRAINT system_settings_updated_by_foreign FOREIGN KEY (updated_by) REFERENCES public.users(id) ON DELETE SET NULL;


--
-- Name: title_presentations title_presentations_defense_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.title_presentations
    ADD CONSTRAINT title_presentations_defense_id_foreign FOREIGN KEY (defense_id) REFERENCES public.defenses(id) ON DELETE RESTRICT;


--
-- Name: title_presentations title_presentations_finalized_by_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.title_presentations
    ADD CONSTRAINT title_presentations_finalized_by_foreign FOREIGN KEY (finalized_by) REFERENCES public.users(id) ON DELETE RESTRICT;


--
-- Name: title_presentations title_presentations_official_form_instance_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.title_presentations
    ADD CONSTRAINT title_presentations_official_form_instance_id_foreign FOREIGN KEY (official_form_instance_id) REFERENCES public.official_form_instances(id) ON DELETE RESTRICT;


--
-- Name: title_presentations title_presentations_official_form_version_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.title_presentations
    ADD CONSTRAINT title_presentations_official_form_version_id_foreign FOREIGN KEY (official_form_version_id) REFERENCES public.official_form_versions(id) ON DELETE RESTRICT;


--
-- Name: title_presentations title_presentations_presentation_completed_by_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.title_presentations
    ADD CONSTRAINT title_presentations_presentation_completed_by_foreign FOREIGN KEY (presentation_completed_by) REFERENCES public.users(id) ON DELETE RESTRICT;


--
-- Name: title_presentations title_presentations_result_recorded_by_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.title_presentations
    ADD CONSTRAINT title_presentations_result_recorded_by_foreign FOREIGN KEY (result_recorded_by) REFERENCES public.users(id) ON DELETE RESTRICT;


--
-- Name: user_onboarding_completions user_onboarding_completions_user_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.user_onboarding_completions
    ADD CONSTRAINT user_onboarding_completions_user_id_foreign FOREIGN KEY (user_id) REFERENCES public.users(id) ON DELETE CASCADE;


--
-- Name: user_signatures user_signatures_user_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.user_signatures
    ADD CONSTRAINT user_signatures_user_id_foreign FOREIGN KEY (user_id) REFERENCES public.users(id) ON DELETE CASCADE;


--
-- Name: academic_terms; Type: ROW SECURITY; Schema: public; Owner: -
--

ALTER TABLE public.academic_terms ENABLE ROW LEVEL SECURITY;

--
-- Name: academic_years; Type: ROW SECURITY; Schema: public; Owner: -
--

ALTER TABLE public.academic_years ENABLE ROW LEVEL SECURITY;

--
-- Name: adviser_assignments; Type: ROW SECURITY; Schema: public; Owner: -
--

ALTER TABLE public.adviser_assignments ENABLE ROW LEVEL SECURITY;

--
-- Name: cache; Type: ROW SECURITY; Schema: public; Owner: -
--

ALTER TABLE public.cache ENABLE ROW LEVEL SECURITY;

--
-- Name: cache_locks; Type: ROW SECURITY; Schema: public; Owner: -
--

ALTER TABLE public.cache_locks ENABLE ROW LEVEL SECURITY;

--
-- Name: colleges; Type: ROW SECURITY; Schema: public; Owner: -
--

ALTER TABLE public.colleges ENABLE ROW LEVEL SECURITY;

--
-- Name: consultation_requests; Type: ROW SECURITY; Schema: public; Owner: -
--

ALTER TABLE public.consultation_requests ENABLE ROW LEVEL SECURITY;

--
-- Name: defense_evaluation_round_panelists; Type: ROW SECURITY; Schema: public; Owner: -
--

ALTER TABLE public.defense_evaluation_round_panelists ENABLE ROW LEVEL SECURITY;

--
-- Name: defense_evaluation_round_students; Type: ROW SECURITY; Schema: public; Owner: -
--

ALTER TABLE public.defense_evaluation_round_students ENABLE ROW LEVEL SECURITY;

--
-- Name: defense_evaluation_rounds; Type: ROW SECURITY; Schema: public; Owner: -
--

ALTER TABLE public.defense_evaluation_rounds ENABLE ROW LEVEL SECURITY;

--
-- Name: defense_evaluation_student_scores; Type: ROW SECURITY; Schema: public; Owner: -
--

ALTER TABLE public.defense_evaluation_student_scores ENABLE ROW LEVEL SECURITY;

--
-- Name: defense_evaluation_student_summaries; Type: ROW SECURITY; Schema: public; Owner: -
--

ALTER TABLE public.defense_evaluation_student_summaries ENABLE ROW LEVEL SECURITY;

--
-- Name: defense_evaluation_summaries; Type: ROW SECURITY; Schema: public; Owner: -
--

ALTER TABLE public.defense_evaluation_summaries ENABLE ROW LEVEL SECURITY;

--
-- Name: defense_evaluations; Type: ROW SECURITY; Schema: public; Owner: -
--

ALTER TABLE public.defense_evaluations ENABLE ROW LEVEL SECURITY;

--
-- Name: defense_panel_assignments; Type: ROW SECURITY; Schema: public; Owner: -
--

ALTER TABLE public.defense_panel_assignments ENABLE ROW LEVEL SECURITY;

--
-- Name: defense_rooms; Type: ROW SECURITY; Schema: public; Owner: -
--

ALTER TABLE public.defense_rooms ENABLE ROW LEVEL SECURITY;

--
-- Name: defense_schedules; Type: ROW SECURITY; Schema: public; Owner: -
--

ALTER TABLE public.defense_schedules ENABLE ROW LEVEL SECURITY;

--
-- Name: defenses; Type: ROW SECURITY; Schema: public; Owner: -
--

ALTER TABLE public.defenses ENABLE ROW LEVEL SECURITY;

--
-- Name: departments; Type: ROW SECURITY; Schema: public; Owner: -
--

ALTER TABLE public.departments ENABLE ROW LEVEL SECURITY;

--
-- Name: document_review_audits; Type: ROW SECURITY; Schema: public; Owner: -
--

ALTER TABLE public.document_review_audits ENABLE ROW LEVEL SECURITY;

--
-- Name: document_review_comments; Type: ROW SECURITY; Schema: public; Owner: -
--

ALTER TABLE public.document_review_comments ENABLE ROW LEVEL SECURITY;

--
-- Name: document_reviews; Type: ROW SECURITY; Schema: public; Owner: -
--

ALTER TABLE public.document_reviews ENABLE ROW LEVEL SECURITY;

--
-- Name: document_upload_audits; Type: ROW SECURITY; Schema: public; Owner: -
--

ALTER TABLE public.document_upload_audits ENABLE ROW LEVEL SECURITY;

--
-- Name: documents; Type: ROW SECURITY; Schema: public; Owner: -
--

ALTER TABLE public.documents ENABLE ROW LEVEL SECURITY;

--
-- Name: faculty_profiles; Type: ROW SECURITY; Schema: public; Owner: -
--

ALTER TABLE public.faculty_profiles ENABLE ROW LEVEL SECURITY;

--
-- Name: failed_jobs; Type: ROW SECURITY; Schema: public; Owner: -
--

ALTER TABLE public.failed_jobs ENABLE ROW LEVEL SECURITY;

--
-- Name: job_batches; Type: ROW SECURITY; Schema: public; Owner: -
--

ALTER TABLE public.job_batches ENABLE ROW LEVEL SECURITY;

--
-- Name: jobs; Type: ROW SECURITY; Schema: public; Owner: -
--

ALTER TABLE public.jobs ENABLE ROW LEVEL SECURITY;

--
-- Name: migrations; Type: ROW SECURITY; Schema: public; Owner: -
--

ALTER TABLE public.migrations ENABLE ROW LEVEL SECURITY;

--
-- Name: milestone_definitions; Type: ROW SECURITY; Schema: public; Owner: -
--

ALTER TABLE public.milestone_definitions ENABLE ROW LEVEL SECURITY;

--
-- Name: milestone_evidences; Type: ROW SECURITY; Schema: public; Owner: -
--

ALTER TABLE public.milestone_evidences ENABLE ROW LEVEL SECURITY;

--
-- Name: model_has_permissions; Type: ROW SECURITY; Schema: public; Owner: -
--

ALTER TABLE public.model_has_permissions ENABLE ROW LEVEL SECURITY;

--
-- Name: model_has_roles; Type: ROW SECURITY; Schema: public; Owner: -
--

ALTER TABLE public.model_has_roles ENABLE ROW LEVEL SECURITY;

--
-- Name: notifications; Type: ROW SECURITY; Schema: public; Owner: -
--

ALTER TABLE public.notifications ENABLE ROW LEVEL SECURITY;

--
-- Name: official_form_actor_assignments; Type: ROW SECURITY; Schema: public; Owner: -
--

ALTER TABLE public.official_form_actor_assignments ENABLE ROW LEVEL SECURITY;

--
-- Name: official_form_definitions; Type: ROW SECURITY; Schema: public; Owner: -
--

ALTER TABLE public.official_form_definitions ENABLE ROW LEVEL SECURITY;

--
-- Name: official_form_instances; Type: ROW SECURITY; Schema: public; Owner: -
--

ALTER TABLE public.official_form_instances ENABLE ROW LEVEL SECURITY;

--
-- Name: official_form_signature_verifications; Type: ROW SECURITY; Schema: public; Owner: -
--

ALTER TABLE public.official_form_signature_verifications ENABLE ROW LEVEL SECURITY;

--
-- Name: official_form_signatures; Type: ROW SECURITY; Schema: public; Owner: -
--

ALTER TABLE public.official_form_signatures ENABLE ROW LEVEL SECURITY;

--
-- Name: official_form_verifications; Type: ROW SECURITY; Schema: public; Owner: -
--

ALTER TABLE public.official_form_verifications ENABLE ROW LEVEL SECURITY;

--
-- Name: official_form_versions; Type: ROW SECURITY; Schema: public; Owner: -
--

ALTER TABLE public.official_form_versions ENABLE ROW LEVEL SECURITY;

--
-- Name: password_reset_tokens; Type: ROW SECURITY; Schema: public; Owner: -
--

ALTER TABLE public.password_reset_tokens ENABLE ROW LEVEL SECURITY;

--
-- Name: permissions; Type: ROW SECURITY; Schema: public; Owner: -
--

ALTER TABLE public.permissions ENABLE ROW LEVEL SECURITY;

--
-- Name: programs; Type: ROW SECURITY; Schema: public; Owner: -
--

ALTER TABLE public.programs ENABLE ROW LEVEL SECURITY;

--
-- Name: research_class_actor_assignments; Type: ROW SECURITY; Schema: public; Owner: -
--

ALTER TABLE public.research_class_actor_assignments ENABLE ROW LEVEL SECURITY;

--
-- Name: research_class_enrollments; Type: ROW SECURITY; Schema: public; Owner: -
--

ALTER TABLE public.research_class_enrollments ENABLE ROW LEVEL SECURITY;

--
-- Name: research_class_group_adviser_histories; Type: ROW SECURITY; Schema: public; Owner: -
--

ALTER TABLE public.research_class_group_adviser_histories ENABLE ROW LEVEL SECURITY;

--
-- Name: research_class_group_adviser_requests; Type: ROW SECURITY; Schema: public; Owner: -
--

ALTER TABLE public.research_class_group_adviser_requests ENABLE ROW LEVEL SECURITY;

--
-- Name: research_class_group_members; Type: ROW SECURITY; Schema: public; Owner: -
--

ALTER TABLE public.research_class_group_members ENABLE ROW LEVEL SECURITY;

--
-- Name: research_class_groups; Type: ROW SECURITY; Schema: public; Owner: -
--

ALTER TABLE public.research_class_groups ENABLE ROW LEVEL SECURITY;

--
-- Name: research_classes; Type: ROW SECURITY; Schema: public; Owner: -
--

ALTER TABLE public.research_classes ENABLE ROW LEVEL SECURITY;

--
-- Name: research_group_adviser_change_requests; Type: ROW SECURITY; Schema: public; Owner: -
--

ALTER TABLE public.research_group_adviser_change_requests ENABLE ROW LEVEL SECURITY;

--
-- Name: research_group_members; Type: ROW SECURITY; Schema: public; Owner: -
--

ALTER TABLE public.research_group_members ENABLE ROW LEVEL SECURITY;

--
-- Name: research_group_milestone_events; Type: ROW SECURITY; Schema: public; Owner: -
--

ALTER TABLE public.research_group_milestone_events ENABLE ROW LEVEL SECURITY;

--
-- Name: research_group_milestones; Type: ROW SECURITY; Schema: public; Owner: -
--

ALTER TABLE public.research_group_milestones ENABLE ROW LEVEL SECURITY;

--
-- Name: research_groups; Type: ROW SECURITY; Schema: public; Owner: -
--

ALTER TABLE public.research_groups ENABLE ROW LEVEL SECURITY;

--
-- Name: research_projects; Type: ROW SECURITY; Schema: public; Owner: -
--

ALTER TABLE public.research_projects ENABLE ROW LEVEL SECURITY;

--
-- Name: research_proposals; Type: ROW SECURITY; Schema: public; Owner: -
--

ALTER TABLE public.research_proposals ENABLE ROW LEVEL SECURITY;

--
-- Name: revision_request_events; Type: ROW SECURITY; Schema: public; Owner: -
--

ALTER TABLE public.revision_request_events ENABLE ROW LEVEL SECURITY;

--
-- Name: revision_requests; Type: ROW SECURITY; Schema: public; Owner: -
--

ALTER TABLE public.revision_requests ENABLE ROW LEVEL SECURITY;

--
-- Name: role_has_permissions; Type: ROW SECURITY; Schema: public; Owner: -
--

ALTER TABLE public.role_has_permissions ENABLE ROW LEVEL SECURITY;

--
-- Name: roles; Type: ROW SECURITY; Schema: public; Owner: -
--

ALTER TABLE public.roles ENABLE ROW LEVEL SECURITY;

--
-- Name: sessions; Type: ROW SECURITY; Schema: public; Owner: -
--

ALTER TABLE public.sessions ENABLE ROW LEVEL SECURITY;

--
-- Name: signature_audits; Type: ROW SECURITY; Schema: public; Owner: -
--

ALTER TABLE public.signature_audits ENABLE ROW LEVEL SECURITY;

--
-- Name: student_profiles; Type: ROW SECURITY; Schema: public; Owner: -
--

ALTER TABLE public.student_profiles ENABLE ROW LEVEL SECURITY;

--
-- Name: title_presentations; Type: ROW SECURITY; Schema: public; Owner: -
--

ALTER TABLE public.title_presentations ENABLE ROW LEVEL SECURITY;

--
-- Name: user_signatures; Type: ROW SECURITY; Schema: public; Owner: -
--

ALTER TABLE public.user_signatures ENABLE ROW LEVEL SECURITY;

--
-- PostgreSQL database dump complete
--


