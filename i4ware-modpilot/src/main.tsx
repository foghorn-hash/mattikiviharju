import { Devvit, useState, SettingScope } from '@devvit/public-api';

Devvit.configure({
  redditAPI: true,
  redis: true,
  http: true,
});

Devvit.addSettings([
  {
    type: 'string',
    name: 'openai_api_key',
    label: 'OpenAI API Key',
    isSecret: true,
    scope: SettingScope.App,
    helpText: 'Required for ModPilot AI to analyze posts and comments.',
  },
]);

// Helper to increment Burnout/Workload Counter
async function incrementWorkload(context: Devvit.Context) {
  const username = context.userId;
  if (!username) return;
  
  const today = new Date().toISOString().split('T')[0];
  const redisKey = `workload:${username}:${today}`;
  
  await context.redis.zIncrBy('mod_workload_scoreboard', 1, username);
  
  const current = await context.redis.get(redisKey);
  const count = current ? parseInt(current, 10) + 1 : 1;
  await context.redis.set(redisKey, count.toString());
  
  if (count > 20) {
    context.ui.showToast('⚠️ Burnout Warning: You have processed over 20 items today. Consider taking a break!');
  }
}

const aiActionForm = Devvit.createForm(
  (data: any) => ({
    title: 'ModPilot AI Case Brief',
    acceptLabel: 'Execute Action',
    cancelLabel: 'Cancel',
    fields: [
      {
        type: 'string',
        name: 'targetId',
        label: 'Target ID (Hidden)',
        defaultValue: data.targetId,
        disabled: true,
      },
      {
        type: 'paragraph',
        name: 'analysis',
        label: `Risk Score: ${data.riskScore}\n\nSummary:\n${data.summary}\n\nRecommendation:\n${data.recommendation}`,
        defaultValue: 'Review the analysis above and select a moderation action below.',
      },
      {
        type: 'select',
        name: 'modAction',
        label: 'Moderator Action',
        options: [
          { label: 'None (Cancel)', value: 'none' },
          { label: 'Approve', value: 'approve' },
          { label: 'Remove', value: 'remove' },
          { label: 'Mark as Spam', value: 'spam' },
          { label: 'Lock', value: 'lock' },
        ],
        defaultValue: [data.defaultAction || 'none'],
      }
    ],
  }),
  async (event, context) => {
    const action = event.values.modAction?.[0] || 'none';
    const targetId = event.values.targetId as string;
    
    if (action === 'none') {
      context.ui.showToast('No action taken.');
      return;
    }

    try {
      if (action === 'approve') {
        await context.reddit.approve(targetId);
      } else if (action === 'remove') {
        await context.reddit.remove(targetId, false);
      } else if (action === 'spam') {
        await context.reddit.remove(targetId, true);
      } else if (action === 'lock') {
        const thing = await context.reddit.getPostById(targetId).catch(() => null);
        if (thing) {
          await thing.lock();
        }
      }

      await incrementWorkload(context);
      context.ui.showToast(`Action "${action}" executed successfully!`);
    } catch (e) {
      console.error(e);
      context.ui.showToast(`Failed to execute action. Ensure you have proper permissions.`);
    }
  }
);

Devvit.addMenuItem({
  location: ['post', 'comment'],
  label: 'ModPilot Analyze',
  onPress: async (event, context) => {
    const apiKey = await context.settings.get('openai_api_key');
    if (!apiKey) {
      context.ui.showToast('OpenAI API Key is missing in app settings.');
      return;
    }

    let content = '';
    let targetId = '';

    if (event.targetId.startsWith('t3_')) {
      const post = await context.reddit.getPostById(event.targetId);
      content = `${post.title}\n\n${post.body || ''}`;
      targetId = post.id;
    } else if (event.targetId.startsWith('t1_')) {
      const comment = await context.reddit.getCommentById(event.targetId);
      content = comment.body;
      targetId = comment.id;
    }

    context.ui.showToast('Analyzing with ModPilot AI...');

    try {
      const prompt = `You are an AI moderator assistant for a Reddit community.
Analyze the following post/comment content and output a JSON object with:
- riskScore: "Critical", "High", "Medium", or "Low"
- summary: A brief 2-3 sentence summary of what happened and why it's risky (e.g. toxicity, spam, scam).
- recommendation: A short recommendation (e.g. "Remove the post", "Mark as spam", "Approve").
- defaultAction: One of "none", "approve", "remove", "spam", or "lock".

Content to analyze:
"${content}"
`;

      const response = await fetch('https://api.openai.com/v1/chat/completions', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'Authorization': `Bearer ${apiKey}`,
        },
        body: JSON.stringify({
          model: 'gpt-4o-mini',
          messages: [{ role: 'user', content: prompt }],
          response_format: { type: 'json_object' },
        }),
      });

      const data = await response.json();
      if (data.error) {
        context.ui.showToast('Error from OpenAI API.');
        return;
      }

      const resultText = data.choices[0].message.content;
      const parsed = JSON.parse(resultText);

      context.ui.showForm(aiActionForm, {
        targetId: targetId,
        riskScore: parsed.riskScore || 'Unknown',
        summary: parsed.summary || 'No summary provided.',
        recommendation: parsed.recommendation || 'No recommendation.',
        defaultAction: parsed.defaultAction || 'none',
      });
    } catch (e) {
      console.error(e);
      context.ui.showToast('Failed to analyze the content.');
    }
  },
});

Devvit.addCustomPostType({
  name: 'ModPilot Dashboard',
  height: 'tall',
  render: (context) => {
    const [workload, setWorkload] = useState(async () => {
      const today = new Date().toISOString().split('T')[0];
      const username = context.userId || 'unknown';
      const key = `workload:${username}:${today}`;
      const count = await context.redis.get(key);
      return count || '0';
    });

    return (
      <vstack padding="medium" gap="medium" alignment="middle center">
        <text size="xxlarge" weight="bold">i4ware® ModPilot AI™</text>
        <text size="large">Community Health & Burnout Dashboard</text>
        <spacer size="medium" />
        
        <hstack gap="medium" alignment="middle center">
          <vstack padding="medium" cornerRadius="medium" border="thick" borderColor="gray">
            <text weight="bold">My Cases Today</text>
            <text size="xlarge" color="red">{workload}</text>
          </vstack>
          <vstack padding="medium" cornerRadius="medium" border="thick" borderColor="gray">
            <text weight="bold">Active Alerts</text>
            <text size="xlarge" color="orange">0</text>
          </vstack>
        </hstack>

        <spacer size="medium" />
        <button
          onPress={async () => {
            const today = new Date().toISOString().split('T')[0];
            const username = context.userId || 'unknown';
            const count = await context.redis.get(`workload:${username}:${today}`);
            setWorkload(count || '0');
          }}
        >
          Refresh Data
        </button>
      </vstack>
    );
  },
});

Devvit.addMenuItem({
  location: 'subreddit',
  label: 'Create ModPilot Dashboard',
  onPress: async (event, context) => {
    const subreddit = await context.reddit.getCurrentSubreddit();
    const post = await context.reddit.submitPost({
      title: 'i4ware® ModPilot AI™ Dashboard',
      subredditName: subreddit.name,
      preview: (
        <vstack padding="medium" cornerRadius="medium">
          <text size="medium">Loading ModPilot Dashboard...</text>
        </vstack>
      ),
    });
    context.ui.showToast(`Dashboard created successfully!`);
    context.ui.navigateTo(post);
  },
});

export default Devvit;
