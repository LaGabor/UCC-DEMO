<?php

namespace Database\Seeders;

use App\Models\Event;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class EventSeeder extends Seeder
{
    public function run(): void
    {
        $user1 = User::query()->where('email', 'user1@gmail.com')->first();
        $user2 = User::query()->where('email', 'user2@gmail.com')->first();

        if (! $user1 || ! $user2) {
            return;
        }

        $huTitles = [
            'Csapat értekezlet',
            'Projekt megbeszélés',
            'Ügyfél hívás',
            'Kód review',
            'Sprint tervezés',
            'Demo nap',
            'Tréning',
            'Értékelő beszélgetés',
            'Workshop',
            'Státusz meeting',
            'Brainstorm',
            'Dokumentáció frissítés',
            'Tesztelés',
            'Deploy előkészítés',
            'Retro',
            'One-on-one',
            'Konferencia hívás',
            'Design review',
            'Backlog refinement',
            'Incident megbeszélés',
            'Onboarding',
            'Knowledge sharing',
            'Éves értékelés',
            'Kliens demo',
            'Technikai workshop',
            'Stakeholder update',
            'Kockázat elemzés',
            'Release planning',
            'Support handover',
            'Következő sprint',
        ];

        $huDescriptions = [
            'Heti csapat szinkron.',
            'Projekt státusz és következő lépések.',
            'Ügyféllel egyeztetés.',
            'Pull requestek átnézése.',
            'Sprint célok és feladatok.',
            'Új funkciók bemutatása.',
            'Új rendszer tréning.',
            'Teljesítmény értékelés.',
            'Interaktív workshop.',
            'Státusz frissítés minden résztvevőnek.',
            'Új ötletek gyűjtése.',
            'Dokumentáció karbantartás.',
            'Manuális és automata tesztek.',
            'Éles környezet deploy.',
            'Sprint retro.',
            'Egyéni megbeszélés.',
            'Vendég előadóval.',
            'UI/UX feedback.',
            'Backlog priorítás.',
            'Incident utómunka.',
            'Új kolléga bevezetés.',
            'Belső know-how megosztás.',
            'Éves célok áttekintése.',
            'Ügyfélnek demo.',
            'Technikai deep dive.',
            'Stakeholdereknek update.',
            'Kockázatok és mérföldkövek.',
            'Következő release tervezés.',
            'Support csapatnak átadás.',
            'Következő sprint feladatok.',
        ];

        $enTitles = [
            'Team meeting',
            'Project sync',
            'Client call',
            'Code review',
            'Sprint planning',
            'Demo day',
            'Training session',
            'Performance review',
            'Workshop',
            'Status meeting',
            'Brainstorm',
            'Documentation update',
            'Testing',
            'Deploy prep',
            'Retrospective',
            'One-on-one',
            'Conference call',
            'Design review',
            'Backlog refinement',
            'Incident review',
            'Onboarding',
            'Knowledge sharing',
            'Annual review',
            'Client demo',
            'Tech workshop',
            'Stakeholder update',
            'Risk analysis',
            'Release planning',
            'Support handover',
            'Next sprint',
        ];

        $enDescriptions = [
            'Weekly team sync.',
            'Project status and next steps.',
            'Client alignment.',
            'Reviewing pull requests.',
            'Sprint goals and tasks.',
            'Presenting new features.',
            'New system training.',
            'Performance evaluation.',
            'Interactive workshop.',
            'Status update for all.',
            'Gathering new ideas.',
            'Documentation maintenance.',
            'Manual and automated tests.',
            'Production deploy.',
            'Sprint retrospective.',
            'Individual meeting.',
            'With guest speaker.',
            'UI/UX feedback.',
            'Backlog prioritization.',
            'Incident follow-up.',
            'New colleague onboarding.',
            'Internal knowledge share.',
            'Annual goals review.',
            'Demo for client.',
            'Technical deep dive.',
            'Update for stakeholders.',
            'Risks and milestones.',
            'Next release planning.',
            'Handover to support.',
            'Next sprint tasks.',
        ];

        $baseDate = Carbon::create(2026, 8, 1, 9, 0, 0);

        for ($i = 0; $i < 30; $i++) {
            Event::create([
                'user_id' => $user1->id,
                'title' => $huTitles[$i],
                'description' => $huDescriptions[$i],
                'occurs_at' => $baseDate->copy()->addDays($i)->addHours($i % 5),
            ]);
        }

        for ($i = 0; $i < 30; $i++) {
            Event::create([
                'user_id' => $user2->id,
                'title' => $enTitles[$i],
                'description' => $enDescriptions[$i],
                'occurs_at' => $baseDate->copy()->addDays($i + 2)->addHours(($i % 5) + 2),
            ]);
        }
    }
}
