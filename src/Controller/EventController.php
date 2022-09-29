<?php

namespace App\Controller;

use App\Entity\Event;
use App\Entity\EventAttachment;
use App\Repository\EventRepository;
use DateTime;
use DateTimeImmutable;
use Exception;
use League\Flysystem\FilesystemOperator;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\Routing\Annotation\Route;

class EventController extends AbstractController
{
    /**
     * @throws Exception
     */
    #[Route('/events', name: 'app_events_index')]
    public function index(Request $request, EventRepository $eventRepository): Response
    {
        $now = new DateTime();
        $month = $request->query->get('m', (int) $now->format('n'));
        $year = $request->query->get('y', (int) $now->format('Y'));

        /** @var Event[] $events */
        $events = $eventRepository->findForMonthAndYear($month, $year);

        $firstDate = (new DateTime(sprintf('%s-%s-01 midnight', $year, $month)));
        $lastDate = (clone $firstDate)->modify('last day of this month');
        if (1 !== (int) $firstDate->format('N')) {
            $firstDate->modify('previous monday');
        }
        if (7 !== (int) $lastDate->format('N')) {
            $lastDate->modify('next sunday 23:59:59');
        }

        $calendar = [];
        for ($currentDate = $firstDate; $currentDate <= $lastDate; $currentDate->modify('+1 day')) {
            $startOfDay = DateTimeImmutable::createFromMutable($currentDate)->setTime(0, 0, 0);
            $endOfDay = DateTimeImmutable::createFromMutable($currentDate->setTime(23, 59, 59));
            $eventsForThisDay = array_filter($events, function (Event $event) use ($startOfDay, $endOfDay) {
                return ($event->getStartsAt() >= $startOfDay && $event->getStartsAt() <= $endOfDay)
                    || ($event->getEndsAt() >= $startOfDay && $event->getEndsAt() <= $endOfDay);
            });
            $calendar[$currentDate->format('Y-m-j')] = $eventsForThisDay;
        }

        return $this->render('event/index.html.twig', [
            'month' => $month,
            'year' => $year,
            'calendar' => $calendar,
        ]);
    }

    #[Route('/events/attachments/{attachment}', name: 'events_attachements_download')]
    public function downloadAttachement(Request $request, EventAttachment $attachment, FilesystemOperator $eventsStorage): Response
    {
        $forceDownload = $request->query->get('forceDownload');
        $contentDisposition = sprintf(
            '%s; filename="%s"',
            $forceDownload ? ResponseHeaderBag::DISPOSITION_ATTACHMENT : ResponseHeaderBag::DISPOSITION_INLINE,
            $attachment->getFile()->getName()
        );

        $response = new StreamedResponse(function () use ($eventsStorage, $attachment) {
            $outputStream = fopen('php://output', 'wb');
            $fileStream = $eventsStorage->readStream($attachment->getFile()->getName());

            stream_copy_to_stream($fileStream, $outputStream);
        }, 200, [
            'Content-Type' => $attachment->getFile()->getMimeType(),
            'Content-Disposition' => $contentDisposition,
            'Content-Length' => $attachment->getFile()->getSize(),
        ]);
        $response->setLastModified($attachment->getUpdatedAt());

        return $response;
    }

    #[Route('/events/{event}')]
    public function showModal(Event $event): Response
    {
        return $this->render('event/show.modal.html.twig', [
            'event' => $event,
        ]);
    }
}