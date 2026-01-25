<?php

namespace App\Enums;

enum MissionStatus: string
{
    case ACTIVE = 'active';
    case COMPLETED = 'completed'; // Combat won, ready to claim? Or combat done?
    // User requested flow: "Claim Reward button appears only when status == 'completed'"
    // But "completeMission is triggered on Claim".
    // This implies a state 'READY_TO_CLAIM' waiting for user action?
    // Let's stick to standard names but map logic carefully.
    // If time is up, it's virtually 'completed' but logically still 'active' until user triggers completion?
    // Or we have a cron job finishing it?
    // Prompt: "Method completeMission... Validates if now() >= ends_at ... Triggers combat ... If won ... claimed"
    // So distinct states:
    // 1. ACTIVE (Running)
    // 2. COMPLETED (Time up, handled by backend? OR wait, prompt says "Claim" calls "completeMission".)
    // Let's use ACTIVE. Frontend sees time is up. User clicks Claim. API /claim calls completeMission.
    // Inside completeMission, we verify time. If success -> update DB to CLAIMED (or history).
    // So really we only need ACTIVE and CLAIMED (History).
    // But if we want a "Mission Log" of past missions, CLAIMED is good.
    // FAILED is also good if lost.

    case CLAIMED = 'claimed';
    case FAILED = 'failed';
}
